<?php

namespace Neos\Flow\Tests\Functional\Persistence\FixturesPHP8;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Class CommonObject
 * Representation of an object handled as "\Doctrine\DBAL\Types\Type::OBJECT"
 */
class CommonObject
{
    protected ?string $foo;

    public function setFoo(?string $foo = null): self
    {
        $this->foo = $foo;
        return $this;
    }

    public function getFoo(): ?string
    {
        return $this->foo;
    }
}
