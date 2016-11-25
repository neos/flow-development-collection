<?php
namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\Context;
use Neos\Eel\InterpretedEvaluator;

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
