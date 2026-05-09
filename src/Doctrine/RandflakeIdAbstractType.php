<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Doctrine;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BigIntType;

/**
 * Randflake ID type abstract class.
 *
 * The database stored value will always be an unencrypted and unencoded 64-bit integer to ensure that IDs are both
 * sortable (by creation time) and cursor-based navigable (for pagination). The settings to encrypt and encode determine
 * whether this field type should transparently encrypt/decrypt and encode/decode the stored value when
 * retrieving/storing to the database, according to what your application expects.
 *
 * Both settings default to null, which means the service configuration will be used. You should typically only force
 * a different value if you have a specific use case, or if you have changed the service configuration since the
 * application has begun to generate live IDs. For example if you need to maintain existing permalinks with a specific
 * entity.
 *
 * Extend this class and override the `setup()` method to set the encrypt and encode options, or leave them null to use
 * the service's configuration. Be sure to call `parent::setup($service);` at the beginning of your override to ensure
 * the service instance is set on this type.
 *
 * When used with the custom generator class both encrypt and encode options will always come from the service.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
abstract class RandflakeIdAbstractType extends BigIntType
{
    public const NAME = "randflake_id_abstract";

    /** @var RandflakeIdService|null $service Instance of the Randflake ID service. */
    protected ?RandflakeIdService $service = null;

    /** @var bool|null $encrypt Whether the ID should be transparently encrypted/decrypted. */
    protected ?bool $encrypt = null;

    /** @var bool|null $encode Whether the ID should be transparently encoded/decoded (in Base32Hex). */
    protected ?bool $encode = null;

    /**
     * Setup this type with an instance of the Randflake ID service.
     * This should be called as part of middleware setup or manually in your application bootstrapping code.
     */
    public function setup(RandflakeIdService $service): self
    {
        $this->service = $service;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!($this->service instanceof RandflakeIdService)) {
            throw new \RuntimeException("RandflakeId type has not been setup with a service instance.");
        }

        if (!is_string($value) || "" === $value) {
            throw new \InvalidArgumentException("Value must be a non-empty string.");
        }

        if (boolval($this->encode)) {
            RandflakeIdService::assertBase32HexStringId($value);
        } else {
            RandflakeIdService::assertNumericStringId($value);
        }
        $valueForDatabase = $value;

        // If the application uses an encoded value, decode it now for storage
        if (boolval($this->encode)) {
            $valueForDatabase = $this->service->decodeId($valueForDatabase);
        }

        if (!ctype_digit($valueForDatabase)) {
            throw new \RuntimeException("Value must be a numeric string.");
        }

        // If the application uses an encrypted value, decrypt it now for storage
        if (boolval($this->encrypt)) {
            $valueForDatabase = $this->service->decryptId($valueForDatabase);
        }

        return $valueForDatabase;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!($this->service instanceof RandflakeIdService)) {
            throw new \RuntimeException("RandflakeId type has not been setup with a service instance.");
        }

        // Ensure that the database value is transformed to a PHP numeric string
        $valueForApplication = null;

        if (is_int($value)) {
            // DBAL 4.0 and later adaptively uses PHP integer type for BIGINT field types when the value is within the
            // signed range, it will need to be converted to a numeric string
            $valueForApplication = $this->service->intToString($value);
        } elseif (is_string($value) && "" !== $value) {
            // DBAL 3.x and earlier always uses PHP string type for BIGINT field types, so we can just validate that its
            // a numeric string
            RandflakeIdService::assertNumericStringId($value);
            $valueForApplication = $value;
        } else {
            // Whatever this is..!
            /**
             * @fixme This should never happen, but occasionally a float is being cast. Further investigation required!
             * For example `1.1836807671202038E+19`
             */
            throw new \InvalidArgumentException("Value must be an integer or a numeric string.");
        }

        if (!ctype_digit($valueForApplication)) {
            throw new \RuntimeException("Value must be a numeric string.");
        }

        // If the application wants an encrypted value, encrypt it now from storage
        if (boolval($this->encrypt)) {
            $valueForApplication = $this->service->encryptId($valueForApplication);
        }

        // If the application wants an encoded value, encode it now from storage
        if (boolval($this->encode)) {
            $valueForApplication = $this->service->encodeId($valueForApplication);
        }

        return $valueForApplication;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        if (!is_string(static::NAME) || "" === static::NAME) {
            throw new \LogicException("Type name must be a non-empty string.");
        }

        return static::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
