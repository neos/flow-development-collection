<?php
namespace Neos\Utility\ObjectHandling\Tests\Unit\Fixture;

/*
 * This file is part of the Neos.Utility.ObjectHandling package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * ArrayAccess class for the Reflection tests
 *
 */
class ArrayAccessClass implements \ArrayAccess
{
    protected string $internalProperty = 'access through forceDirectAccess';

    protected array $array = [];

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->array);
    }

    public function offsetGet($offset): mixed
    {
        return $this->array[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->array[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->array[$offset]);
    }
}
