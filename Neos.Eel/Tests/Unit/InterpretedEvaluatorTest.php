<?php

declare(strict_types=1);

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

require_once "AbstractEvaluatorTestcase.php";


/**
 * Interpreted evaluator test
 */
final class InterpretedEvaluatorTest extends AbstractEvaluatorTestcase
{
    /**
     * @return Context
     */
    protected function createEvaluator()
    {
        return new InterpretedEvaluator();
    }
}
