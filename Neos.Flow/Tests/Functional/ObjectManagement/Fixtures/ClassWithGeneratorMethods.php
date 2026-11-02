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

use Neos\Flow\Annotations as Flow;

/**
 * A class with generator methods, using "yield" and "yield from"
 */
class ClassWithGeneratorMethods
{
    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    protected string $prefix = 'item-';

    /**
     * @return \Generator<int, string>
     */
    public function generateItems(): \Generator
    {
        yield $this->prefix . 'one';
        yield $this->prefix . 'two';
    }

    /**
     * @return \Generator<int, string>
     */
    public function generateItemsWithDelegation(): \Generator
    {
        yield $this->prefix . 'zero';
        yield from $this->generateItems();
        yield $this->prefix . 'three';
    }

    /**
     * @return \Generator<string, int>
     */
    public function generateKeyedValues(): \Generator
    {
        yield 'a' => 1;
        yield 'b' => 2;
    }

    /**
     * A generator which receives values through send() and returns a final result
     *
     * @return \Generator<int, int>
     */
    public function generateSums(): \Generator
    {
        $sum = 0;
        while (true) {
            $received = yield $sum;
            if ($received === null) {
                break;
            }
            $sum += $received;
        }
        return $sum;
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
