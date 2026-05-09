<?php

declare(strict_types=1);

namespace Adambean\Bundle\RandflakeIdBundle\Doctrine;

use Adambean\Bundle\RandflakeIdBundle\Service\RandflakeIdService;

/**
 * Randflake ID type class: Explicitly decrypted and decoded type.
 *
 * @author Adam Reece <1108717+Adambean@users.noreply.github.com>
 * @license MIT
 */
final class RandflakeIdExplicitDdType extends RandflakeIdAbstractType
{
    public const NAME = "randflake_id_explicit_dd";

    /**
     * {@inheritDoc}
     */
    public function setup(RandflakeIdService $service): self
    {
        parent::setup($service);

        $this->encrypt  = false;
        $this->encode   = false;

        return $this;
    }
}
