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

use TYPO3\Flow\Annotations as Flow;

/**
 * An evaluator that compiles expressions down to PHP code
 *
 * This simple implementation will lazily parse and evaluate the generated PHP
 * code into a function with a name built from the hashed expression.
 *
 * @Flow\Scope("singleton")
 */
class CompilingEvaluator implements EelEvaluatorInterface {

	/**
	 * @var array
	 */
	protected $newExpressions = array();

	/**
	 * @Flow\Inject(lazy=false)
	 * @var \TYPO3\Flow\Cache\Frontend\PhpFrontend
	 */
	protected $expressionCache;

	/**
	 * Initialize the Evaluator
	 */
	public function initializeObject() {
		$this->expressionCache->requireOnce('cachedExpressionClosures');
	}

	/**
	 * Shutdown the Evaluator
	 */
	public function shutdownObject() {
		if (count($this->newExpressions) > 0) {
			$changesToPersist = FALSE;
			$codeToBeCached = $this->expressionCache->get('cachedExpressionClosures');
			/**
			 * At this point a race condition could happen, that we try to prevent with an additional check.
			 * So we compare the evaluated expressions during this request with the methods the cache has at
			 * this point and only add methods that are not present. Only if we added anything we write the cache.
			 */
			foreach ($this->newExpressions as $functionName => $newExpression) {
				if (strpos($codeToBeCached, $functionName) === FALSE) {
					$codeToBeCached .= $newExpression . chr(10);
					$changesToPersist = TRUE;
				}
			}

			if ($changesToPersist) {
				$this->expressionCache->set('cachedExpressionClosures', $codeToBeCached);
			}
		}
	}

	/**
	 * Evaluate an expression under a given context
	 *
	 * @param string $expression
	 * @param Context $context
	 * @return mixed
	 */
	public function evaluate($expression, Context $context) {
		$expression = trim($expression);
		$identifier = md5($expression);
		$functionName = 'expression_' . $identifier;

		if (!function_exists($functionName)) {
			$code = $this->generateEvaluatorCode($expression);
			$functionDeclaration = 'function ' . $functionName . '($context){return ' . $code . ';}';
			$this->newExpressions[$functionName] = $functionDeclaration;
			eval($functionDeclaration);
		}

		$result = $functionName($context);
		if ($result instanceof Context) {
			return $result->unwrap();
		} else {
			return $result;
		}
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
		$parser = new CompilingEelParser($expression);
		$result = $parser->match_Expression();

		if ($result === FALSE) {
			throw new ParserException(sprintf('Expression "%s" could not be parsed.', $expression), 1344513194);
		} elseif ($parser->pos !== strlen($expression)) {
			throw new ParserException(sprintf('Expression "%s" could not be parsed. Error starting at character %d: "%s".', $expression, $parser->pos, substr($expression, $parser->pos)), 1327682383);
		} elseif (!array_key_exists('code', $result)) {
			throw new ParserException(sprintf('Parser error, no code in result %s ', json_encode($result)), 1334491498);
		}
		return $result['code'];
	}

}
