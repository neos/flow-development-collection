<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A simple test context that is registered as a global AOP object
 */
class TestContext
{
    /**
     * @return string
     */
    public function getNameOfTheWeek()
    {
        return 'Robbie';
    }
}
