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

use RuntimeException;

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

    /**
     * @throws RuntimeException
     */
    public function alwaysTrue(bool $throwException = true): true
    {
        if ($throwException) {
            throw new RuntimeException('The $throwException flag in ' . __METHOD__ . ' was not set to false.');
        }
        return true;
    }

    /**
     * @throws RuntimeException
     */
    public function alwaysFalse(bool $throwException = true): false
    {
        if ($throwException) {
            throw new RuntimeException('The $throwException flag in ' . __METHOD__ . ' was not set to false.');
        }
        return false;
    }

    // This needs https://github.com/laminas/laminas-code/pull/186 to be merged in order to work:
//    /**
//     * @throws RuntimeException
//     */
//    public function alwaysNull(bool $throwException = true): null
//    {
//        if ($throwException) {
//            throw new RuntimeException('The $throwException flag in ' . __METHOD__ . ' was not set to false.');
//        }
//        return null;
//    }

    /**
     * @throws RuntimeException
     */
    public function alwaysNever(bool $throwException = true): never
    {
        if ($throwException) {
            throw new RuntimeException('The $throwException flag in ' . __METHOD__ . ' was not set to false.');
        }
        throw new RuntimeException('Here is the expected exception.', 1686132896);
    }

    public function __toString(): string
    {
        return get_class($this);
    }
}
