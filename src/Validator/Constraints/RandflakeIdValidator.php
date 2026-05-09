<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Validator\Constraints;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Randflake ID validation validator class.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
class RandflakeIdValidator extends ConstraintValidator
{
    /**
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!($constraint instanceof RandflakeIdConstraint)) {
            throw new UnexpectedTypeException($constraint, RandflakeIdConstraint::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_scalar($value) && !($value instanceof \Stringable)) {
            throw new UnexpectedValueException($value, "string");
        }

        $value = strval($value);

        try {
            RandflakeIdService::assertValidId(
                $value,
                match($constraint->format) {
                    RandflakeIdConstraint::FORMAT_ENCODED   => true,
                    RandflakeIdConstraint::FORMAT_UNENCODED => false,
                    RandflakeIdConstraint::FORMAT_ANY       => null,
                }
            );
        } catch (\Throwable $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCause($e)
                ->addViolation()
            ;
        }
    }
}
