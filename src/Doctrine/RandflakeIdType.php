<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Doctrine;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;

/**
 * Randflake ID type class: Standard.
 * This will use the encryption and encoding settings from the service.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
final class RandflakeIdType extends RandflakeIdAbstractType
{
    public const NAME = "randflake_id";

    /**
     * {@inheritDoc}
     */
    public function setup(RandflakeIdService $service): self
    {
        parent::setup($service);

        $this->encrypt  = $service->isEncrypted();
        $this->encode   = $service->isEncoded();

        return $this;
    }
}
