<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Exception\InvalidPolicyException;

/**
 * A condition generator used as an eel context to orchestrate the different sql condition generators.
 */
class ConditionGenerator {

	/**
	 * Entity type the currently parsed expression relates to
	 * @var string
	 */
	protected $entityType;

	/**
	 * @param $entityType
	 * @return void
	 * @throws InvalidPolicyException
	 */
	public function isType($entityType) {
		if ($this->entityType !== NULL) {
			throw new InvalidPolicyException('You can use only exactly one isType definition in an entity privilege matcher. Already had "' . $this->entityType . '" and got another "' . $entityType . '"!', 1402989326);
		}
		$this->entityType = $entityType;
	}

	/**
	 * @param SqlGeneratorInterface $expression
	 * @return NotExpressionGenerator
	 */
	public function notExpression(SqlGeneratorInterface $expression) {
		return new NotExpressionGenerator($expression);
	}

	/**
	 * @return ConjunctionGenerator
	 */
	public function conjunction() {
		$expressions = func_get_args();
		return new ConjunctionGenerator(array_filter($expressions, function($expression) { return $expression instanceof SqlGeneratorInterface; }));
	}

	/**
	 * @return DisjunctionGenerator
	 */
	public function disjunction() {
		$expressions = func_get_args();
		return new DisjunctionGenerator(array_filter($expressions, function($expression) { return $expression instanceof SqlGeneratorInterface; }));
	}

	/**
	 * @param string $path The property path
	 * @return PropertyConditionGenerator
	 */
	public function property($path) {
		return new PropertyConditionGenerator($path);
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return $this->entityType;
	}
}