<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Command;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "randflakeid:secret:generate",
    description: "Generate a random secret key for use in your configuration."
)]
final class GenerateSecretCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption("exclude-symbols", null, InputOption::VALUE_NONE, "Exclude symbols from the generated secret, use numbers and letters only.");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $secret = RandflakeIdService::generateSecret(boolval($input->getOption("exclude-symbols")));

        $output->writeln($secret);

        return Command::SUCCESS;
    }
}
