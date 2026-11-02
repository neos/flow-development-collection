<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81;

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
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;

/**
 * A class which uses the first class callable syntax introduced with PHP 8.1
 */
class ClassWithFirstClassCallables
{
    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    protected string $greeting = 'Hello';

    public function greet(string $name): string
    {
        return $this->greeting . ' ' . $name;
    }

    public static function shout(string $message): string
    {
        return strtoupper($message) . '!';
    }

    protected function whisper(string $message): string
    {
        return strtolower($message) . '…';
    }

    /**
     * Creates a callable from a public method, from within the class itself
     */
    public function getGreeterCallable(): \Closure
    {
        return $this->greet(...);
    }

    /**
     * Creates a callable from a protected method, from within the class itself
     */
    public function getWhispererCallable(): \Closure
    {
        return $this->whisper(...);
    }

    /**
     * Creates a callable from a static method, from within the class itself
     */
    public function getShouterCallable(): \Closure
    {
        return static::shout(...);
    }

    /**
     * Uses a first class callable in place, so the call happens inside the original class
     *
     * @param array<int, string> $names
     * @return array<int, string>
     */
    public function greetAll(array $names): array
    {
        return array_map($this->greet(...), $names);
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
