# Randflake ID Symfony Bundle

Symfony bundle for my PHP implementation of Randflake ID generator.

This bundle implements my [Randflake ID library package](https://github.com/adambean/randflake-id-php) into your Symfony project with the Doctrine ORM/DBAL integration, providing a custom column type and ID generator for seamless integration, including transparent handling of encrypted and encoded IDs. It also provides a validation constraint for validating Randflake IDs in your Symfony application, and console commands for generating secrets, generating IDs, and inspecting IDs.

While not required, you should familiarise yourself with the library package documentation before using this bundle, as it is beneficial to understand the configuration and usage of this bundle.

**⚠️ This library is currently a work in progress, and should not be used in a production environment until it has undergone peer review.**

## Requirements

- PHP† 8.1 or later
- Composer
- Symfony 6.4 or later
- Doctrine bundle 2.13 or later

† **This library requires a 64-bit build of PHP to function.** Check your `PHP_INT_SIZE`, which **must** be at least `8`:

```sh
php -r 'echo PHP_INT_SIZE;'
```

## Installation

Add a Composer package as is usual for PHP projects:

```sh
composer require adambean/randflake-id-symfony-bundle
```

## Configuration

### Options

With exception of the node ID and lease times, all configuration options **must** be consistent across all nodes.

#### RANDFLAKE_ID_NODE_ID

**Required, unsigned integer, 0 to 131071:** The node ID for this instance. This **must** be unique across all nodes generating IDs to avoid collisions. You **must** keep track of your node ID allocations.

Refer the the library documentation **Nodes** section for more details on managing node IDs.

If you only have one processing node, you can set this to `0`.

#### RANDFLAKE_ID_SECRET

**Required, string, 16 bytes:** The shared secret for your application ID pool. This **must** be a 16-byte string, and **must** be identical across all nodes of your application.

You can generate a secret using the `randflakeid:secret:generate` console command, and optionally pass in the "--excludeSymbols" option to generate a secret without symbols (digits and mixed case letters only).

#### RANDFLAKE_ID_ENCRYPTED

**Optional, boolean (default: true):** Whether IDs should be encrypted within your application. Encrypted IDs are unpredictable thus do not leak the creation time, node ID, and ID sequence to end users.

You should never change this setting after your application has begun to generate IDs, though can optionally override this on a per-column basis using the `encrypt` option.

#### RANDFLAKE_ID_ENCODED

**Optional, boolean (default: true):** Whether IDs should be encoded within your application. Encoded IDs will be shortened from an integer up to 20 digits down to a string up to 13 characters.

You should never change this setting after your application has begun to generate IDs, though can optionally override this on a per-column basis using the `encode` option.

#### RANDFLAKE_ID_LEASE_START and RANDFLAKE_ID_LEASE_END

**Optional, integer timestamp (default: 0):** The start and end of the node lease time (in seconds since Unix epoch) for the node ID allocation. This is used to allow for temporary nodes to exist with a limited lease time, allowing you to free up a the node ID later on for another node to replace it.

If the node attempts to generate an ID outside of its lease time an exception will be thrown to block it.

If the node ID allocation is permanent, you can leave both of these at `0` to indicate no limited lease time.

### Environment variables

Define the required environment variables:

```env
RANDFLAKE_ID_NODE_ID=0
RANDFLAKE_ID_SECRET=ThisIsNotSecret!
RANDFLAKE_ID_ENCRYPTED=1
RANDFLAKE_ID_ENCODED=1
RANDFLAKE_ID_LEASE_START=
RANDFLAKE_ID_LEASE_END=
```

### Package

Optional explicit configuration (defaults to the env variables above):

```yaml
# config/packages/adambean_randflake_id.yaml
adambean_randflake_id:
  node_id: '%env(int:default::RANDFLAKE_ID_NODE_ID)%'
  secret: '%env(string:RANDFLAKE_ID_SECRET)%'
  encrypted: '%env(bool:default::RANDFLAKE_ID_ENCRYPTED)%'
  encoded: '%env(bool:default::RANDFLAKE_ID_ENCODED)%'
  lease_start: '%env(int:default::RANDFLAKE_ID_LEASE_START)%'
  lease_end: '%env(int:default::RANDFLAKE_ID_LEASE_END)%'
  time_source: '%env(default::int:RANDFLAKE_ID_TIME_SOURCE)%'
```

## Service

The service is registered as `Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService` and exposes:

- Static functions:
  - `assertNumericStringId(string $id): void`
  - `assertBase32HexStringId(string $id): void`
  - `assertValidId(string $id, ?bool $expectEncoded = null): void`
  - `generateSecret(bool $excludeSymbols = false): string`
- Instance functions:
  - `changeLease(int $leaseEnd): void`
  - `getNodeId(): int`
  - `getLeaseStart(bool $absolute = false): int`
  - `getLeaseEnd(bool $absolute = false): int`
  - `getTimeSource(): ?int`
  - `isNumericStringIdValid(string $id): void`
  - `isEncodedStringIdValid(string $id): void`
  - `isIdValid(string $id): void`
  - `intToString(int $id): string`
  - `stringToInt(string $id): int`
  - `encryptId(string $idRaw): string`
  - `decryptId(string $idEncrypted): string`
  - `encodeId(string $idPlain): string`
  - `decodeId(string $idEncoded): string`
  - `generate(?bool $encrypted = null, ?bool $encoded = null): string`
  - `inspect(string $id, ?bool $isEncrypted = null): array`

### Example usage in a controller

```php
<?php

declare(strict_types=1);

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;
use Adambean\RandflakeId\Exception\RandflakeIdException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ExampleController
{
    /**
     * Get an entity.
     *
     * @param non-empty-string $id Entity ID, in its encrypted and encoded form if configured
     */
    public function view(Request $request, RandflakeIdService $randflakeIdService, string $id): Response
    {
        try {
            $randflakeIdService->isIdValid($id);
        } catch (RandflakeIdException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $entity = $this->getRepository(Entity::class)->find($id);

        // ...

        return new Response(...);
    }

    /**
     * Make an entity ID at runtime, for some reason.
     */
    public function makeId(Request $request, RandflakeIdService $randflakeIdService): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException("Only AJAX requests allowed.");
        }

        // Make an ID at runtime
        $id = $randflakeIdService->generate();

        return new JsonResponse(["id" => $id]);
    }
}
```

## Console commands

### Dump service configuration

Dump the Randflake ID service configuration:

```sh
bin/console randflakeid:config:dump
```

### Generate a secret string

Generate a random secret key for use in your configuration:

```sh
bin/console randflakeid:secret:generate --exclude-symbols?
```

Optionally you can exclude symbols to generate a secret with digits and mixed case letters only.

### Generate an ID

Generate a new Randflake ID:

```sh
bin/console randflakeid:generate --raw|encrypted? --encoded?
```

Either encrypted or raw (but not both) can be specified to force ID encryption (or not). If neither are specified, the command will use the configuration to decide.

### Inspect an ID

Inspect a Randflake ID:

```sh
bin/console randflakeid:inspect <id> --raw|encrypted?
```

Either encrypted or raw (but not both) can be specified to indicate the format of the ID. If neither are specified, the command will use the configuration to decide.

### Translate an ID

Translate a Randflake ID either in or out of storage:

```sh
bin/console randflakeid:translate <id> --app|storage
```

Either application or storage (but not both) can be specified to indicate the direction of translation.

This is useful for taking an ID in its encoded and encrypted form as used in your application and translating it to the raw numeric string form used for storage, or vice versa.

- **App**: Translate application visible ID to the raw storage ID.
- **Storage**: Translate raw storage ID to the application visible ID.

For example if you have an ID from a URL parameter you need to find in your database, you would use the "**app**" direction to see what it would be in your database. The "**storage**" direction would be used to see what an ID in your database would look like as a URL parameter.

## Doctrine ORM integration

### Custom column types

Use the `RandflakeIdType::class` or "**randflake_id**" column type class.

```php
#[ORM\Column(name: "id", type: RandflakeIdType::NAME, unique: true)]
protected ?string $id;
```

This field type will assume that they should encrypt and encode according to your service configuration.

It is possible to override this behaviour with 4 additional custom column types. These should only be overridden on columns that have already undergone production use with different settings to that of the current service configuration, typically to ensure that references (such as permalinks) continue to work as expected.

```php
/**
 * This property MUST always be unencrypted and unencoded to the application.
 */
#[ORM\Column(name: "identifier_explicit_dd", type: RandflakeIdExplicitDdType::NAME, unique: true)]
protected ?string $identifierExplicitDd;

/**
 * This property MUST always be unencrypted but encoded to the application.
 */
#[ORM\Column(name: "identifier_explicit_de", type: RandflakeIdExplicitDeType::NAME, unique: true)]
protected ?string $identifierExplicitDe;

/**
 * This property MUST always be encrypted but unencoded to the application.
 */
#[ORM\Column(name: "identifier_explicit_ed", type: RandflakeIdExplicitEdType::NAME, unique: true)]
protected ?string $identifierExplicitEd;

/**
 * This property MUST always be encrypted and encoded to the application.
 */
#[ORM\Column(name: "identifier_explicit_ee", type: RandflakeIdExplicitEeType::NAME, unique: true)]
protected ?string $identifierExplicitEe;
```

These alternative field types only impact the application level value of the ID. The database stored value is always an unencrypted and unencoded numeric string.

### Custom ID generator

Use the `RandflakeIdGenerator::class` or "**adambean.randflake_id_generator**" ID generator class custom strategy for your ID column.

```php
#[ORM\GeneratedValue(strategy: "CUSTOM")]
#[ORM\CustomIdGenerator(class: RandflakeIdGenerator::class)]
```

### Example entity

```php
<?php

declare(strict_types=1);

use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdGenerator;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdType;
use Adambean\Bundle\RandflakeIdBundle\Validator\Constraints\RandflakeIdConstraint;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "example")]
#[UniqueEntity(fields: ["id"])]
class Example
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: RandflakeIdType::class, unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: RandflakeIdGenerator::class)]
    #[RandflakeIdConstraint]
    protected ?string $id = null;
}
```

### Entity repository

When making use of standard entity repository functions no special behaviour is required to handle the Randflake ID column types, as the DBAL types will transparently decrypt and decode values as needed to search the database. This means you can use the standard repository functions as normal, such as:

- `find()`
- `findOneBy()`
- `findBy()`
- `findAll()`
- `count()`

However if you require a repository functions making use of custom DQL or `QueryBuilder`, an extra step is necessary to make the ORM aware that it may need to decrypt and/or decode your given values (specified in parameters) prior to executing the query.

There are two options to achieve this.

#### Option 1: Explicitly specify the DBAL type name in `setParameter()`

The simplest option is to explicitly specify the DBAL type name the third parameter of your `setParameter()` calls for any parameters that relate to Randflake ID columns, which will trigger the DBAL type converters to transparently decrypt and decode the value as needed before it reaches the database. **This is best if you can assure that the column type will be consistent.**

```php
<?php

declare(strict_types=1);

use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdType;
use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdExplicitDeType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

final class ExampleRepository extends ServiceEntityRepository
{
    public function findByCustomQuery(string $id, string $alwaysEncryptedAndDecodedId): ?Example
    {
        return $this->createQueryBuilder("e")
            ->andWhere("e.id = :id")
            ->setParameter("id", $id, RandflakeIdType::NAME)
            ->andWhere("e.alwaysEncryptedAndDecodedId = :alwaysEncryptedAndDecodedId")
            ->setParameter("alwaysEncryptedAndDecodedId", $alwaysEncryptedAndDecodedId, RandflakeIdExplicitDeType::NAME)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
```

#### Option 2: Use the `setTypedParameter()` helper function

The other option is to extend your repository class from our `RandflakeIdServiceEntityRepository` class instead of the standard `ServiceEntityRepository`. This provides a helper function `setTypedParameter()` that will automatically determine the correct DBAL type to use for the parameter based on the entity property name you provide. **This is best if you have a mix of Randflake ID column types, or want to avoid hardcoding DBAL type names in your repository code.**

```php
<?php

declare(strict_types=1);

use Adambean\Bundle\RandflakeIdBundle\Doctrine\RandflakeIdServiceEntityRepository;

final class ExampleRepository extends RandflakeIdServiceEntityRepository
{
    public function findByCustomQuery(string $id, string $alwaysEncryptedAndDecodedId): ?Example
    {
        return $this->createQueryBuilder("e")
            ->andWhere("e.id = :id")
            ->setTypedParameter("id", $id, "id")
            ->andWhere("e.alwaysEncryptedAndDecodedId = :alwaysEncryptedAndDecodedId")
            ->setTypedParameter("alwaysEncryptedAndDecodedId", $alwaysEncryptedAndDecodedId, "alwaysEncryptedAndDecodedId")
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
```

## Development & contributing

Ensure any contribution is compatible with the earliest supported PHP and Symfony versions.

Clean up code style to the accepted standards:

```sh
composer cs:fix
```

Ensure static analysis checks pass:

```sh
composer stan
```

Ensure all tests pass:
*(With exception of the chained functions in the Symfony bundle configuration tree builder due to mixed returns from `end()`.)*

```sh
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

- Symfony bundle and Randflake ID PHP implementation: [Adam "Adambean" Reece](https://github.com/adambean)
- Original Randflake ID implementation: [lemon-mint](https://github.com/lemon-mint) at [GoSuda](https://github.com/gosuda)
