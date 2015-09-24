<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


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
