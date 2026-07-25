<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Command;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Adambean\RandflakeId\Exception\RandflakeIdException;
use DateTimeZone;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "randflakeid:inspect",
    description: "Inspect a Randflake ID.\nEither encrypted or raw (but not both) can be specified to indicate the format of the ID. If neither are specified, the command will use the configuration to decide."
)]
final class InspectCommand extends Command
{
    public function __construct(readonly private RandflakeIdService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument("id", InputArgument::REQUIRED, "Randflake ID, either as an integer or a Base32Hex encoded string.")
            ->addOption("encrypted", null, InputOption::VALUE_NONE, "ID is encrypted.")
            ->addOption("raw", null, InputOption::VALUE_NONE, "ID is not encrypted.")
            ->addUsage("--encrypted to indicate the ID is encrypted and should be decrypted before inspection.")
            ->addUsage("--raw to indicate the ID is not encrypted and should be inspected as-is.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $idArgument = $input->getArgument("id");
        if (!is_string($idArgument) || "" === ($idArgument = strtolower(trim($idArgument)))) {
            $output->writeln("ID must be a non-empty string.");
            return Command::INVALID;
        }

        $idIsEncoded = null;
        try {
            if (ctype_digit($idArgument)) {
                RandflakeIdService::assertNumericStringId($idArgument);
                $idIsEncoded = false;
            } else {
                RandflakeIdService::assertBase32HexStringId($idArgument);
                $idIsEncoded = true;
            }
        } catch (\InvalidArgumentException $e) {
            $output->writeln("ID must be a numeric string or a Base32Hex encoded string.");
            return Command::INVALID;
        } catch (\Exception $e) {
            $output->writeln(sprintf("An unexpected error occurred: %s", $e->getMessage()));
            return Command::FAILURE;
        }

        $id = $idIsEncoded ? $this->service->decodeId($idArgument) : $idArgument;
        if (!ctype_digit($id)) {
            $output->writeln("ID must be a numeric string.");
            return Command::INVALID;
        }

        $useEncrypted = $input->hasOption("encrypted") ? boolval($input->getOption("encrypted")) : null;
        $useRaw = $input->hasOption("raw") ? boolval($input->getOption("raw")) : null;

        if (true === $useEncrypted && true === $useRaw) {
            $output->writeln("Pick only one of --encrypted or --raw.");
            return Command::INVALID;
        }

        $details = null;
        try {
            $details = $this->service->inspect($id, true === $useEncrypted ? true : (true === $useRaw ? false : null));
        } catch (RandflakeIdException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln(sprintf("An unexpected error occurred: %s", $e->getMessage()));
            return Command::FAILURE;
        }

        if ($idIsEncoded) {
            $output->writeln(sprintf("ID encoded: %s", $idArgument));
        }
        $output->writeln(sprintf("ID: %s", $id));
        $output->writeln(sprintf("Timestamp: %s", $details["timestamp"]));
        $output->writeln(sprintf("Timestamp (UTC): %s", $details["timestampUtc"]));
        $output->writeln(sprintf("Node ID: %s", $details["nodeId"]));
        $output->writeln(sprintf("Sequence: %s", $details["sequence"]));

        $dtUtc = \DateTimeImmutable::createFromFormat("U", $details["timestampUtc"], new DateTimeZone("UTC"));
        if (!($dtUtc instanceof \DateTimeImmutable)) {
            $output->writeln("Failed to create DateTimeImmutable from timestamp.");
            return Command::FAILURE;
        }
        $output->writeln(sprintf("Date/time (UTC): %s", $dtUtc->format("Y-m-d H:i:s")));

        $tzLocal = new DateTimeZone(date_default_timezone_get());
        $dtLocal = $dtUtc->setTimezone($tzLocal);
        $output->writeln(sprintf("Date/time (local: %s): %s", $tzLocal->getName(), $dtLocal->format("Y-m-d H:i:s")));

        return Command::SUCCESS;
    }
}
