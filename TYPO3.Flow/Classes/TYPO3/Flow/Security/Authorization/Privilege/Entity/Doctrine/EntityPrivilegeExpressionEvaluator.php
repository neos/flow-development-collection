<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Eel\Context;
use TYPO3\Eel\ParserException;
use TYPO3\Flow\Annotations as Flow;

/**
 * An evaluator that compiles expressions down to PHP code
 *
 * This simple implementation will lazily parse and evaluate the generated PHP
 * code into a function with a name built from the hashed expression.
 *
 * @Flow\Scope("singleton")
 */
class EntityPrivilegeExpressionEvaluator extends CompilingEvaluator {

	/**
	 * Evaluate an expression under a given context
	 *
	 * @param string $expression
	 * @param Context $context
	 * @return mixed
	 */
	public function evaluate($expression, Context $context) {
		$identifier = md5($expression);
		$functionName = 'expression_' . $identifier;

		if (!function_exists($functionName)) {
			$code = $this->generateEvaluatorCode($expression);
			$functionDeclaration = 'function ' . $functionName . '($context){return ' . $code . ';}';
			$this->newExpressions[$functionName] = $functionDeclaration;
			eval($functionDeclaration);
		}

		$result = $functionName($context)->unwrap();
		$entityType = $context->unwrap()->getEntityType();
		return array('entityType' => $entityType, 'conditionGenerator' => $result);
	}

	/**
	 * Internal generator method
	 *
	 * Used by unit tests to debug generated PHP code.
	 *
	 * @param string $expression
	 * @return string
	 * @throws ParserException
	 */
	protected function generateEvaluatorCode($expression) {
		$parser = new EntityPrivilegeExpressionParser($expression);
		/** @var boolean|array $result */
		$result = $parser->match_Expression();

		if ($result === FALSE) {
			throw new ParserException(sprintf('Expression "%s" could not be parsed.', $expression), 1416933186);
		} elseif ($parser->pos !== strlen($expression)) {
			throw new ParserException(sprintf('Expression "%s" could not be parsed. Error starting at character %d: "%s".', $expression, $parser->pos, substr($expression, $parser->pos)), 1416933203);
		} elseif (!array_key_exists('code', $result)) {
			throw new ParserException(sprintf('Parser error, no code in result %s ', json_encode($result)), 1416933192);
		}
		return $result['code'];
	}
}
