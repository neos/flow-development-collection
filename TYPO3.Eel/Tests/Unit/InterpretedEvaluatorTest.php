<?php
namespace TYPO3\Eel\Tests\Unit;

/*
 * This file is part of the TYPO3.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\Context;
use TYPO3\Eel\InterpretedEvaluator;

/**
 * Interpreted evaluator test
 */
class InterpretedEvaluatorTest extends AbstractEvaluatorTest
{
    /**
     * @return Context
     */
    protected function createEvaluator()
    {
        return new InterpretedEvaluator();
    }
}
