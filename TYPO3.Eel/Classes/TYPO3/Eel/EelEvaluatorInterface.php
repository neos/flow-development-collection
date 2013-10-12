<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An Eel evaluator interface
 */
interface EelEvaluatorInterface {

	/**
	 * Evaluate an expression under a given context
	 *
	 * @param string $expression The expression to evaluate
	 * @param Context $context The context to provide to the expression
	 * @return mixed The evaluated expression
	 */
	public function evaluate($expression, Context $context);

}
