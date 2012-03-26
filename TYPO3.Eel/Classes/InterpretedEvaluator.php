<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An expression evalutator that interprets expressions
 *
 * There is no generated PHP code so this evaluator does not perform very
 * good in multiple invocations.
 */
class InterpretedEvaluator {

	/**
	 * Evaluate an expression under a given context
	 *
	 * @param string $expression
	 * @param Context
	 * @return mixed
	 */
	public function evaluate($expression, Context $context) {
		$parser = new InterpretedEelParser($expression, $context);
		$res = $parser->match_Expression();

		if ($parser->pos !== strlen($expression)) {
			throw new Exception(sprintf('The Eel Expression "%s" could not be parsed. Error at character %d.', $expression, $parser->pos+1), 1327682383);
		}

		if (!array_key_exists('val', $res)) {
			throw new \Exception('No value in result: ' . json_encode($res));
		} else if ($res['val'] instanceof Context) {
			return $res['val']->unwrap();
		} else {
			return $res['val'];
		}
	}

}
?>