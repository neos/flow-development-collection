<?php
namespace Neos\Flow\Tests\Unit\Reflection\Fixture;

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
 * Class using the #[\Override] attribute, which only exists since PHP 8.3
 */
class ClassWithOverrideAttribute implements \JsonSerializable
{
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [];
    }
}
