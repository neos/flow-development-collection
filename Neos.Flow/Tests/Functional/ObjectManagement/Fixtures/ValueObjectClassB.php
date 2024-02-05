<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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
 * A class in the style of a value object
 */
class ValueObjectClassB implements \JsonSerializable
{
    public function __construct(
        readonly public string $value,
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('Value must not be empty', 1684166315);
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
