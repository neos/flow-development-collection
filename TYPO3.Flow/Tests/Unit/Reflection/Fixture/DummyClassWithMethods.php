<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Dummy class for the Reflection tests
 *
 */
class DummyClassWithMethods
{
    /**
     * Some method
     *
     * @firsttag
     * @secondtag a
     * @secondtag b
     * @param string $arg1 Argument 1 documentation
     * @return void
     */
    public function firstMethod($arg1, &$arg2, \stdClass $arg3, $arg4 = 'default')
    {
    }

    /**
     * Some method
     *
     * @return void
     */
    protected function secondMethod()
    {
    }
}
