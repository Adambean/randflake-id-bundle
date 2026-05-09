<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Doctrine;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

/**
 * Randflake ID custom generator class.
 *
 * This class will use the Randflake ID service to generate new IDs according to the service configuration. When used
 * with this generator both encrypted and encoded options will always come from the service, regardless of any
 * explicit settings on the entity field.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
final class RandflakeIdGenerator extends AbstractIdGenerator
{
    /** @var RandflakeIdService|null Service instance, set by the bundle on boot. */
    private static ?RandflakeIdService $service = null;

    /**
     * Set the service instance.
     * Called by {@see AdambeanRandflakeIdBundle::boot()} so that Doctrine can instantiate this class without arguments.
     */
    public static function setService(RandflakeIdService $service): void
    {
        self::$service = $service;
    }

    /**
     * {@inheritDoc}
     *
     * @param object|null $entity The entity for which an ID is being generated.
     */
    public function generateId(EntityManagerInterface $em, $entity): mixed
    {
        $service = self::$service;
        if (!($service instanceof RandflakeIdService)) {
            throw new \RuntimeException(sprintf(
                "%s has not been initialised. Ensure the bundle is registered and booted.",
                self::class
            ));
        }

        // The generator is called by Doctrine during the flush, therefore it must have a non-encrypted and non-encoded
        // ID to store
        return $service->generate(false, false);
    }
}
