<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Command;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "randflakeid:config:dump",
    description: "Dump the Randflake ID service configuration."
)]
final class DumpConfigCommand extends Command
{
    public function __construct(readonly private RandflakeIdService $service)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->service->getConfiguration();

        $configAsJson = null;
        try {
            $configAsJson = json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

            if ("" === ($configAsJson = trim($configAsJson))) {
                throw new \RuntimeException("JSON encoding did not return a non-empty string.");
            }
        } catch (\JsonException $e) {
            $output->writeln(sprintf("Failed to encode configuration as JSON: %s", $e->getMessage()));
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln(sprintf("An unexpected error occurred: %s", $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln($configAsJson);

        return Command::SUCCESS;
    }
}
