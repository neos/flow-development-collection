<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Eel\InterpretedEvaluator;

/**
 * Interpreted evaluator test
 */
class InterpretedEvaluatorTest extends AbstractEvaluatorTest
{
    /**
     * @return \TYPO3\Eel\Context
     */
    protected function createEvaluator()
    {
        return new InterpretedEvaluator();
    }
}
