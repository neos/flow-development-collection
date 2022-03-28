<?php

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\BasicClass;

class ClassWithoutNamespace
{

    protected $someService;

    /**
     * @param BasicClass $someService
     */
    public function injectSomeService(BasicClass $someService)
    {
        $this->someService = $someService;
    }

    /**
     * Some method
     *
     * @param string $argument
     * @param bool $flag
     * @return string
     * @throws Exception
     */
    public function doSomething(string $argument, bool $flag = false): string
    {
        if ($flag) {
            throw new Exception('Something went wrong');
        }
        return $argument;
    }

    /**
     * @return static
     */
    public static function aStaticFunction(): static
    {
        return new static();
    }

    /**
     * @return void
     */
    public function shutdownObject(): void
    {
    }
}
