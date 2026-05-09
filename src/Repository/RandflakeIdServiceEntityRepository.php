<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Randflake ID base repository class for entities that use Randflake ID column types.
 *
 * Extend this in place of {@see ServiceEntityRepository} to gain access to `setTypedParameter()`, which automatically
 * looks up the DBAL column type for a given entity property from the class metadata and passes it to
 * {@see QueryBuilder::setParameter()}. This ensures that custom converters, such as the Randflake ID decrypt/decode
 * chain, are applied to query parameter values in the same transparent way they are applied when persisting or using
 * `findBy()` / `findOneBy()`.
 *
 * This is required because Doctrine only applies `Type::convertToDatabaseValue()` automatically for:
 * - Entity field values during flush (via UnitOfWork → BasicEntityPersister)
 * - Criteria in `find()`, `findBy()`, and `findOneBy()` (BasicEntityPersister resolves the column type)
 *
 * It does NOT apply it for raw DQL and QueryBuilder parameters, because Doctrine cannot infer the column type from a
 * DQL string. Passing the type as the third argument to `setParameter()` is the correct Doctrine mechanism to trigger
 * type conversion there.
 *
 * Extending your repository class from this is not required if you are happy to explicitly pass through the column type
 * name to `setParameter()` directly, e.g. `$qb->setParameter('id', $id, RandflakeIdType::NAME)`. However using the
 * `setTypedParameter()` function allows you to reduce the number of `use` declarations in your repository class, and
 * determines the correct DBAL type for the given entity property automatically.
 *
 * @template T of object
 * @template-extends ServiceEntityRepository<T>
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
abstract class RandflakeIdServiceEntityRepository extends ServiceEntityRepository
{
    /**
     * Look up the DBAL mapping type name for an entity property via the class metadata.
     * Returns `null` if the property is not a mapped field (e.g. it is an association).
     *
     * @param non-empty-string $property Entity property name.
     *
     * @return non-empty-string|null
     * The DBAL type name for the given property, or `null` if the property is not a mapped field.
     */
    final protected function getTypeOfProperty(string $property): ?string
    {
        if ("" === ($property = trim($property))) {
            throw new \InvalidArgumentException("Property name cannot be empty.");
        }

        $metadata = $this->getClassMetadata();

        if (!$metadata->hasField($property)) {
            return null;
        }

        $typeName = $metadata->getTypeOfField($property);
        if (!is_string($typeName) || "" === $typeName) {
            return null;
        }

        return $typeName;
    }

    /**
     * Bind a parameter to a `QueryBuilder` passing the correct DBAL type for the given entity property so that column
     * type converters are applied to the value.
     *
     * Use this in place of {@see QueryBuilder::setParameter()} for any column that may use a custom DBAL type
     * (Randflake ID or otherwise), so that the value is transparently converted (e.g. decrypted and decoded) before it
     * reaches the database.
     *
     * Example:
     * ```php
     * $this->createQueryBuilder("entity")
     *     ->where("entity.alwaysDecodedProperty = :thatId")
     *     ->setTypedParameter($qb, "thatId", $alwaysDecodedValue, "alwaysDecodedProperty")
     *     ->getQuery()
     *     ->getOneOrNullResult()
     * ;
     * ```
     *
     * @param QueryBuilder     $qb       The query builder instance to set the parameter on.
     * @param non-empty-string $param    Parameter placeholder name (without the leading ":").
     * @param mixed            $value    The value to bind.
     * @param non-empty-string $property The entity property name whose column type should be used.
     *
     * @return self<T>
     */
    final protected function setTypedParameter(QueryBuilder $qb, string $param, mixed $value, string $property): self
    {
        if ("" === ($param = trim($param))) {
            throw new \InvalidArgumentException("Parameter name cannot be empty.");
        }

        if ("" === ($property = trim($property))) {
            throw new \InvalidArgumentException("Property name cannot be empty.");
        }

        $qb->setParameter($param, $value, $this->getTypeOfProperty($property));

        return $this;
    }
}
