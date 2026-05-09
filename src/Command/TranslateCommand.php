<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Command;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Adambean\RandflakeId\Exception\RandflakeIdException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "randflakeid:translate",
    description: "Translate a Randflake ID either in or out of storage..\nEither application or storage (but not both) can be specified to indicate the direction of translation."
)]
final class TranslateCommand extends Command
{
    public function __construct(readonly private RandflakeIdService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument("id", InputArgument::REQUIRED, "Randflake ID, either as an integer or a Base32Hex encoded string.")
            ->addOption("app", null, InputOption::VALUE_NONE, "Translate from application visible ID to the raw storage ID.")
            ->addOption("storage", null, InputOption::VALUE_NONE, "Translate from raw storage ID to the application visible ID.")
            ->addUsage("--app translates from an application visible ID to the raw storage ID.")
            ->addUsage("--storage translates from a raw storage ID to the application visible ID.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $idInput = $input->getArgument("id");

        if (!is_string($idInput) || "" === ($idInput = strtolower(trim($idInput)))) {
            $output->writeln("ID must be a non-empty string.");
            return Command::INVALID;
        }

        try {
            RandflakeIdService::assertValidId($idInput);
        } catch (\InvalidArgumentException $e) {
            $output->writeln($e->getMessage());
            return Command::INVALID;
        } catch (\Exception $e) {
            $output->writeln(sprintf("An unexpected error occurred: %s", $e->getMessage()));
            return Command::FAILURE;
        }

        $directionFromApplication = $input->hasOption("app") ? boolval($input->getOption("app")) : null;
        $directionFromStorage = $input->hasOption("storage") ? boolval($input->getOption("storage")) : null;

        if (true === $directionFromApplication && true === $directionFromStorage) {
            $output->writeln("Pick only one of --app or --storage.");
            return Command::INVALID;
        }

        $id = $idInput;
        try {
            if (true === $directionFromApplication) {
                if ($this->service->isEncoded()) {
                    $id = $this->service->decodeId($id);
                }

                if ($this->service->isEncrypted()) {
                    $id = $this->service->decryptId($id);
                }
            } elseif (true === $directionFromStorage) {
                if ($this->service->isEncrypted()) {
                    $id = $this->service->encryptId($id);
                }

                if ($this->service->isEncoded()) {
                    $id = $this->service->encodeId($id);
                }
            } else {
                $output->writeln("Pick a direction of translation with either --app or --storage.");
                return Command::INVALID;
            }
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
