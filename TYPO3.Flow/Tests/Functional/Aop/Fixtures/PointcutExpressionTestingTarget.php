<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A target class for testing pointcut expressions
 */
class PointcutExpressionTestingTarget
{
    /**
     * @return boolean
     */
    public function testSettingFilter()
    {
        return false;
    }
}
