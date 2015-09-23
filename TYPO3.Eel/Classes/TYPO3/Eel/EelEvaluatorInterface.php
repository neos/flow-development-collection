<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
