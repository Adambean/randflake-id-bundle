<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Randflake ID validation constraint class.
 *
 * This class can be used with Symfony"s Validator component to validate that a given value is a valid Randflake ID
 * according to the service configuration. The validation will check that the value is a valid 64-bit integer and
 * optionally check that it can be decrypted and/or decoded according to the service settings.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class RandflakeIdConstraint extends Constraint
{
    /** @var string The ID must be in encoded (Base32Hex) format. */
    public const FORMAT_ENCODED = "encoded";
    /** @var string The ID must be in decoded (numeric string) format. */
    public const FORMAT_UNENCODED = "unencoded";
    /** @var string The ID can be in any format. */
    public const FORMAT_ANY = "any";

    protected const FORMATS = [
        self::FORMAT_ENCODED,
        self::FORMAT_UNENCODED,
        self::FORMAT_ANY,
    ];

    /**
     * @var string[] $formats
     */
    protected static $formats = self::FORMATS;

    public string $format = self::FORMAT_ANY;

    public string $message = "This is not a valid Randflake ID.";

    /**
     * {@inheritDoc}
     */
    public function __construct(
        ?string $format = null,
        ?string $message = null,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);

        if (null !== $format) {
            if (!in_array($format, self::FORMATS, true)) {
                throw new \InvalidArgumentException(sprintf("Invalid format \"%s\" for \"format\" option.", $format));
            }
            $this->format = $format;
        }

        if (null !== $message) {
            $this->message = $message;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return literal-string
     */
    public function getDefaultOption()
    {
        return "format";
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions(): array
    {
        return ["format"];
    }
}
