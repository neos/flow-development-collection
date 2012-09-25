<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Declares a method as an after throwing advice to be triggered
 * after any pointcut matching the given expression throws an exception.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class AfterThrowing {

	/**
	 * The pointcut expression. (Can be given as anonymous argument.)
	 * @var string
	 */
	public $pointcutExpression;

	/**
	 * @param array $values
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $values) {
		if (!isset($values['value']) && !isset($values['pointcutExpression'])) {
			throw new \InvalidArgumentException('An AfterThrowing annotation must specify a pointcut expression.', 1318456616);
		}
		$this->pointcutExpression = isset($values['pointcutExpression']) ? $values['pointcutExpression'] : $values['value'];
	}

}

?>