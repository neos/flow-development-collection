<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class TargetClassWithPhp8Features
{
    public function methodWithUnionTypes(string|int $aStringOrInteger, int $aNumber, TargetClassWithPhp8Features $anObject): string
    {
        return "{$aStringOrInteger} and {$aNumber} and {$anObject}";
    }

    public function __invoke(string $aString, mixed $something, bool $aFlag): mixed
    {
        return $aFlag ? $aString : $something;
    }

    public function __toString(): string
    {
        return get_class($this);
    }
}
