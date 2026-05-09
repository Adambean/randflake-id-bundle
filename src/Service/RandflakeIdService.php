<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Service;

use Adambean\RandflakeId\Generator;
use Adambean\RandflakeId\RandflakeId;

/**
 * Randflake ID generator service for Symfony applications with Doctrine DBAL.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 *
 * @phpstan-import-type BundleConfiguredArray from \Adambean\Bundle\RandflakeIdBundle\AdambeanRandflakeIdBundle
 * @phpstan-import-type RandflakeIdDetailsArray from \Adambean\RandflakeId\Generator
 */
final class RandflakeIdService
{
    public const SECRET_HASH_ALGO = "sha256";

    private Generator $generator;
    private string $secretHash = "";
    private bool $encrypted = false;
    private bool $encoded = false;

    /**
     * Constructor.
     *
     * @param non-negative-int $nodeId
     * Node ID. (Between 0 to 131071 inclusive.)
     *
     * @param non-empty-string $secret
     * Secret key. (Must be 16 bytes long.)
     *
     * @param bool $encrypted
     * Whether IDs should be encrypted within your application.
     *
     * @param bool $encoded
     * Whether IDs should be encoded within your application.
     *
     * @param int $leaseStart
     * Lease start timestamp relative to epoch offset. If not provided, 0 will be assumed.
     *
     * @param int $leaseEnd
     * Lease end timestamp relative to epoch offset. If not provided, the maximum timestamp will be assumed.
     *
     * @param int|null $timeSource
     * Optional time source for testing. If not provided, `time()` will be used. (Not required outside of testing.)
     *
     * @see Generator::__construct() For parameter details and validation.
     */
    public function __construct(
        int $nodeId,
        string $secret,
        bool $encrypted = true,
        bool $encoded = true,
        int $leaseStart = 0,
        int $leaseEnd = RandflakeId::MAX_TIMESTAMP,
        ?int $timeSource = null,
    ) {
        $this->generator = new Generator(
            $nodeId,
            $secret,
            $leaseStart,
            $leaseEnd,
            $timeSource
        );

        $this->secretHash   = hash(self::SECRET_HASH_ALGO, $secret);
        $this->encrypted    = $encrypted;
        $this->encoded      = $encoded;
    }

    public function getSecretHash(): string
    {
        return $this->secretHash;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function isEncoded(): bool
    {
        return $this->encoded;
    }

    /**
     * @see RandflakeId::assertNumericStringId()
     */
    public static function assertNumericStringId(string $id): void
    {
        RandflakeId::assertNumericStringId($id);
    }

    /**
     * @see RandflakeId::assertBase32HexStringId()
     */
    public static function assertBase32HexStringId(string $id): void
    {
        RandflakeId::assertBase32HexStringId($id);
    }

    /**
     * @see RandflakeId::assertValidId()
     */
    public static function assertValidId(string $id, ?bool $expectEncoded = null): void
    {
        RandflakeId::assertValidId($id, $expectEncoded);
    }

    /**
     * @return literal-string
     *
     * @see Generator::generateSecret()
     */
    public static function generateSecret(bool $excludeSymbols = false): string
    {
        return Generator::generateSecret($excludeSymbols);
    }

    /**
     * @param non-negative-int $leaseEnd
     *
     * @see Generator::changeLease()
     */
    public function changeLease(int $leaseEnd): void
    {
        $this->generator->changeLease($leaseEnd);
    }

    /**
     * @return non-negative-int
     *
     * @see Generator::getNodeId()
     */
    public function getNodeId(): int
    {
        return $this->generator->getNodeId();
    }

    /**
     * @see Generator::getLeaseStart()
     */
    public function getLeaseStart(): int
    {
        return $this->generator->getLeaseStart();
    }

    /**
     * @see Generator::getLeaseEnd()
     */
    public function getLeaseEnd(): int
    {
        return $this->generator->getLeaseEnd();
    }

    /**
     * @see Generator::getTimeSource()
     */
    public function getTimeSource(): ?int
    {
        return $this->generator->getTimeSource();
    }

    /**
     * @param numeric-string $id
     *
     * @see Generator::isNumericStringIdValid()
     */
    public function isNumericStringIdValid(string $id): void
    {
        $this->generator->isNumericStringIdValid($id);
    }

    /**
     * @param non-empty-string $id
     *
     * @see Generator::isEncodedStringIdValid()
     */
    public function isEncodedStringIdValid(string $id): void
    {
        $this->generator->isEncodedStringIdValid($id);
    }

    /**
     * @param non-empty-string $id
     *
     * @see Generator::isIdValid()
     */
    public function isIdValid(string $id): void
    {
        $this->generator->isIdValid($id);
    }

    /**
     * @return numeric-string
     *
     * @see Generator::intToString()
     */
    public function intToString(int $id): string
    {
        return $this->generator->intToString($id);
    }

    /**
     * @param numeric-string $id
     *
     * @see Generator::stringToInt()
     */
    public function stringToInt(string $id): int
    {
        return $this->generator->stringToInt($id);
    }

    /**
     * @param numeric-string $idRaw
     *
     * @return numeric-string
     *
     * @see Generator::encryptId()
     */
    public function encryptId(string $idRaw): string
    {
        return $this->generator->encryptId($idRaw);
    }

    /**
     * @param numeric-string $idEncrypted
     *
     * @return numeric-string
     *
     * @see Generator::decryptId()
     */
    public function decryptId(string $idEncrypted): string
    {
        return $this->generator->decryptId($idEncrypted);
    }

    /**
     * @param numeric-string $idPlain
     *
     * @return non-empty-string
     *
     * @see Generator::encodeId()
     */
    public function encodeId(string $idPlain): string
    {
        return $this->generator->encodeId($idPlain);
    }

    /**
     * @param non-empty-string $idEncoded
     *
     * @return numeric-string
     *
     * @see Generator::decodeId()
     */
    public function decodeId(string $idEncoded): string
    {
        return $this->generator->decodeId($idEncoded);
    }

    /**
     * @return ($encoded is true ? non-empty-string : numeric-string)
     *
     * @see Generator::generate()
     */
    public function generate(?bool $encrypted = null, ?bool $encoded = null): string
    {
        return $this->generator->generate($encrypted ?? $this->encrypted, $encoded ?? $this->encoded);
    }

    /**
     * @param numeric-string $id
     *
     * @return RandflakeIdDetailsArray
     *
     * @see Generator::inspect()
     */
    public function inspect(string $id, ?bool $isEncrypted = null): array
    {
        return $this->generator->inspect($id, $isEncrypted ?? $this->encrypted);
    }

    /**
     * @return BundleConfiguredArray
     */
    public function getConfiguration(): array
    {
        return [
            "node_id"       => $this->getNodeId(),
            "secret_hash"   => $this->getSecretHash(),
            "encrypted"     => $this->isEncrypted(),
            "encoded"       => $this->isEncoded(),
            "lease_start"   => $this->getLeaseStart(),
            "lease_end"     => $this->getLeaseEnd(),
            "time_source"   => $this->getTimeSource(),
        ];
    }
}
