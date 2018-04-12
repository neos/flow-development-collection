<?php
namespace Neos\Eel;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An Eel evaluator interface
 */
interface EelEvaluatorInterface
{
    /**
     * Evaluate an expression under a given context
     *
     * @param string $expression The expression to evaluate
     * @param Context $context The context to provide to the expression
     * @return mixed The evaluated expression
     */
    public function evaluate($expression, Context $context);
}
