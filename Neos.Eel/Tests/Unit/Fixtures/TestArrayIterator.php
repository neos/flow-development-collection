<?php

namespace Neos\Eel\Tests\Unit\Fixtures;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class TestArrayIterator implements \Iterator
{

    /**
     * @var array
     */
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public static function fromArray(array $array): self
    {
        return new self($array);
    }

    public function current()
    {
        return current($this->array);
    }

    public function next()
    {
        next($this->array);
    }

    public function key()
    {
        return key($this->array);
    }

    public function valid()
    {
        return current($this->array) !== false;
    }

    public function rewind()
    {
        reset($this->array);
    }
}
