<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle;

use Adambean\Bundle\RandflakeIdBundle\Command\DumpConfigCommand;
use Adambean\Bundle\RandflakeIdBundle\Command\GenerateCommand;
use Adambean\Bundle\RandflakeIdBundle\Command\GenerateSecretCommand;
use Adambean\Bundle\RandflakeIdBundle\Command\InspectCommand;
use Adambean\Bundle\RandflakeIdBundle\Command\TranslateCommand;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdExplicitDdType;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdExplicitDeType;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdExplicitEdType;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdExplicitEeType;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdGenerator;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdType;
use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Adambean\RandflakeId\RandflakeId;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Randflake ID generator bundle for Symfony applications.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 *
 * @phpstan-type BundleConfigurationArray array{
 *  node_id: non-negative-int,
 *  secret: non-empty-string,
 *  encrypted: bool,
 *  encoded: bool,
 *  lease_start: int,
 *  lease_end: int,
 *  time_source: ?int,
 * }
 *
 * @phpstan-type BundleConfiguredArray array{
 *  node_id: non-negative-int,
 *  secret_hash: string,
 *  encrypted: bool,
 *  encoded: bool,
 *  lease_start: int,
 *  lease_end: int,
 *  time_source: ?int,
 * }
 */
final class AdambeanRandflakeIdBundle extends AbstractBundle
{
    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        parent::boot();

        $service = $this->container->get(RandflakeIdService::class);
        if (!($service instanceof RandflakeIdService)) {
            throw new \RuntimeException(sprintf("Expected service \"%s\" to be an instance of %s.", RandflakeIdService::class, RandflakeIdService::class));
        }

        // Setup and register column types
        $dbalTypeRegistry = Type::getTypeRegistry();

        foreach ([
            RandflakeIdType::class,
            RandflakeIdExplicitDdType::class,
            RandflakeIdExplicitDeType::class,
            RandflakeIdExplicitEdType::class,
            RandflakeIdExplicitEeType::class,
        ] as $typeClass) {
            if (!$dbalTypeRegistry->has($typeClass::NAME)) {
                /** @var RandflakeIdType $type */
                $type = new $typeClass();
                $type->setup($service);
                $dbalTypeRegistry->register($typeClass::NAME, $type);
            }
        }

        // Setup ID generator
        RandflakeIdGenerator::setService($service);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->integerNode("node_id")
                    ->info("The node ID to use for generating Randflake IDs. Must be unique for each deployed node generating IDs to prevent duplication.")
                    ->defaultValue("%env(int:default::RANDFLAKE_ID_NODE_ID)%")
                    ->min(0)
                    ->max(RandflakeId::MAX_NODE)
                ->end() // node_id
                ->scalarNode("secret")
                    ->info(sprintf("The secret key used for encrypting IDs. Must be exactly %d characters long. This should be a secure, random string in production.", RandflakeId::SECRET_LENGTH))
                    ->defaultValue("%env(string:RANDFLAKE_ID_SECRET)%")
                    ->cannotBeEmpty()
                    ->example(RandflakeId::SILLY_SECRET)
                ->end() // secret
                ->booleanNode("encrypted")
                    ->info("Whether IDs should be encrypted.")
                    ->defaultValue("%env(bool:default::RANDFLAKE_ID_ENCRYPTED)%")
                ->end() // encrypted
                ->booleanNode("encoded")
                    ->info("Whether IDs should be encoded.")
                    ->defaultValue("%env(bool:default::RANDFLAKE_ID_ENCODED)%")
                ->end() // encoded
                ->integerNode("lease_start")
                    ->info("The start of the valid timestamp range (in seconds since Unix epoch) for generated IDs. Defaults to the current time or the EPOCH_OFFSET if the current time is before it.")
                    ->defaultValue("%env(int:default::RANDFLAKE_ID_LEASE_START)%")
                    ->min(0)
                    ->max(RandflakeId::MAX_TIMESTAMP)
                ->end() // lease_start
                ->integerNode("lease_end")
                    ->info("The end of the valid timestamp range (in seconds since Unix epoch) for generated IDs. Defaults to the maximum timestamp allowed by the Randflake ID format.")
                    ->defaultValue("%env(int:default::RANDFLAKE_ID_LEASE_END)%")
                    ->min(0)
                    ->max(RandflakeId::MAX_TIMESTAMP)
                ->end() // lease_end
                ->integerNode("time_source")
                    ->info("The source of the current time for generating IDs, or null/zero to use the system time. (Not required outside of testing.)")
                    ->defaultNull()
                    ->defaultValue("%env(default::int:RANDFLAKE_ID_TIME_SOURCE)%")
                ->end() // time_source
            ->end()
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @param BundleConfigurationArray $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Parameters

        $container->parameters()
            ->set("adambean_randflake_id.node_id", $config["node_id"])
            ->set("adambean_randflake_id.secret", $config["secret"])
            ->set("adambean_randflake_id.encrypted", $config["encrypted"])
            ->set("adambean_randflake_id.encoded", $config["encoded"])
            ->set("adambean_randflake_id.lease_start", $config["lease_start"])
            ->set("adambean_randflake_id.lease_end", $config["lease_end"])
            ->set("adambean_randflake_id.time_source", $config["time_source"])
        ;

        // Service

        $container->services()
            ->set("adambean.randflake_id_service", RandflakeIdService::class)
            ->arg('$nodeId', $config["node_id"])
            ->arg('$secret', $config["secret"])
            ->arg('$encrypted', $config["encrypted"])
            ->arg('$encoded', $config["encoded"])
            ->arg('$leaseStart', $config["lease_start"])
            ->arg('$leaseEnd', $config["lease_end"])
            ->arg('$timeSource', $config["time_source"])
            ->public()
            ->autowire()
        ;

        $container->services()
            ->alias(RandflakeIdService::class, "adambean.randflake_id_service")
            ->public()
        ;

        // Doctrine DBAL column types

        foreach ([
            RandflakeIdType::class              => "adambean.randflake_id_type",
            RandflakeIdExplicitDdType::class    => "adambean.randflake_id_explicit_dd_type",
            RandflakeIdExplicitDeType::class    => "adambean.randflake_id_explicit_de_type",
            RandflakeIdExplicitEdType::class    => "adambean.randflake_id_explicit_ed_type",
            RandflakeIdExplicitEeType::class    => "adambean.randflake_id_explicit_ee_type",
        ] as $typeClass => $alias) {
            $container->services()
                ->set($alias, $typeClass)
                ->tag("doctrine.dbal.type", ["name" => $typeClass::NAME])
            ;

            $container->services()
                ->alias($typeClass, $alias)
            ;
        }

        // Doctrine ORM custom generator

        $container->services()
            ->set("adambean.randflake_id_generator", RandflakeIdGenerator::class)
            ->public()
            ->tag("doctrine.id_generator", ["name" => "randflake_id_generator"])
        ;

        $container->services()
            ->alias(RandflakeIdGenerator::class, "adambean.randflake_id_generator")
        ;

        // Commands

        $container->services()
            ->set("adambean.randflake_id_command.dump_config", DumpConfigCommand::class)
            ->tag("console.command")
            ->autowire()
        ;

        $container->services()
            ->set("adambean.randflake_id_command.generate_secret", GenerateSecretCommand::class)
            ->tag("console.command")
            ->autowire()
        ;

        $container->services()
            ->set("adambean.randflake_id_command.generate", GenerateCommand::class)
            ->tag("console.command")
            ->autowire()
        ;

        $container->services()
            ->set("adambean.randflake_id_command.inspect", InspectCommand::class)
            ->tag("console.command")
            ->autowire()
        ;

        $container->services()
            ->set("adambean.randflake_id_command.translate", TranslateCommand::class)
            ->tag("console.command")
            ->autowire()
        ;
    }
}
