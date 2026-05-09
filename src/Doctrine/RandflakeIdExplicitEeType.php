<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Doctrine;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;

/**
 * Randflake ID type class: Explicitly encrypted and encoded type.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
final class RandflakeIdExplicitEeType extends RandflakeIdAbstractType
{
    public const NAME = "randflake_id_explicit_ee";

    /**
     * {@inheritDoc}
     */
    public function setup(RandflakeIdService $service): self
    {
        parent::setup($service);

        $this->encrypt  = true;
        $this->encode   = true;

        return $this;
    }
}
