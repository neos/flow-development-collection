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


/**
 * A target class for testing the AOP framework
 *
 */
class TargetClass02
{
    public $afterReturningAdviceWasInvoked = false;

    /**
     * @param mixed $foo
     * @return mixed
     */
    public function publicTargetMethod($foo)
    {
        return $this->protectedTargetMethod($foo);
    }

    /**
     * @param mixed $foo
     * @return mixed
     */
    protected function protectedTargetMethod($foo)
    {
        return $foo;
    }
}
