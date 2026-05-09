<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Command;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Adambean\RandflakeId\Exception\RandflakeIdException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "randflakeid:generate",
    description: "Generate a new Randflake ID.\nEither encrypted or raw (but not both) can be specified to force ID encryption (or not). If neither are specified, the command will use the configuration to decide."
)]
final class GenerateCommand extends Command
{
    public function __construct(readonly private RandflakeIdService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption("encrypted", null, InputOption::VALUE_NONE, "Generate an encrypted ID.")
            ->addOption("raw", null, InputOption::VALUE_NONE, "Generate a raw ID.")
            ->addOption("encoded", null, InputOption::VALUE_NONE, "Encode the generated ID in Base32Hex instead of a numeric string.")
            ->addUsage("--encrypted to generate an encrypted ID.")
            ->addUsage("--raw to generate a raw ID.")
            ->addUsage("--encoded to encode the generated ID in Base32Hex instead of a numeric string.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $useEncrypted   = $input->hasOption("encrypted") ? boolval($input->getOption("encrypted")) : null;
        $useRaw         = $input->hasOption("raw") ? boolval($input->getOption("raw")) : null;
        $useEncoded     = $input->hasOption("encoded") ? boolval($input->getOption("encoded")) : null;

        if (true === $useEncrypted && true === $useRaw) {
            $output->writeln("Pick only one of --encrypted or --raw.");
            return Command::INVALID;
        }

        $id = null;
        try {
            $id = $this->service->generate(
                true === $useEncrypted ? true : (true === $useRaw ? false : null),
                $useEncoded ?? null
            );
        } catch (RandflakeIdException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln(sprintf("An unexpected error occurred: %s", $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln($id);

        return Command::SUCCESS;
    }
}
