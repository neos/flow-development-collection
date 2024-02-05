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
 * A readonly class in the style of a value object
 */
readonly class ValueObjectClassA implements \JsonSerializable
{
    public function __construct(
        readonly public string $value,
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('Value must not be empty', 1684151596);
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
