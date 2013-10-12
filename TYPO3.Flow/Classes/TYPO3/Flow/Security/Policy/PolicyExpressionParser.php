<?php
namespace TYPO3\Flow\Security\Policy;

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
 * A specialized pointcut expression parser tailored to policy expressions
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class PolicyExpressionParser extends \TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser {

	/**
	 * @var array The resources array from the configuration.
	 */
	protected $methodResourcesTree = array();

	/**
	 * Performs a circular reference detection and calls the (parent) parse function afterwards
	 *
	 * @param string $pointcutExpression The pointcut expression to parse
	 * @param array $methodResourcesTree The method resources tree
	 * @param array $trace A trace of all visited pointcut expression, used for circular reference detection
	 * @return \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite A composite of class-filters, method-filters and pointcuts
	 * @throws \TYPO3\Flow\Security\Exception\CircularResourceDefinitionDetectedException
	 * @throws \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function parseMethodResources($pointcutExpression, array $methodResourcesTree, array &$trace = array()) {
		if (!is_string($pointcutExpression) || strlen($pointcutExpression) === 0) {
			throw new \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException('Pointcut expression must be a valid string, ' . gettype($pointcutExpression) . ' given.', 1168874738);
		}
		if (count($methodResourcesTree) > 0) {
			$this->methodResourcesTree = $methodResourcesTree;
		}

		$pointcutFilterComposite = new \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite();
		$pointcutExpressionParts = preg_split(parent::PATTERN_SPLITBYOPERATOR, $pointcutExpression, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($partIndex = 0; $partIndex < count($pointcutExpressionParts); $partIndex += 2) {
			$operator = ($partIndex > 0) ? trim($pointcutExpressionParts[$partIndex - 1]) : '&&';
			$expression = trim($pointcutExpressionParts[$partIndex]);

			if ($expression[0] === '!') {
				$expression = trim(substr($expression, 1));
				$operator .= '!';
			}

			if (strpos($expression, '(') === FALSE) {
				if (in_array($expression, $trace)) {
					throw new \TYPO3\Flow\Security\Exception\CircularResourceDefinitionDetectedException('A circular reference was detected in the security policy resources definition. Look near: ' . $expression, 1222028842);
				}
				$trace[] = $expression;
				$this->parseDesignatorPointcut($operator, $expression, $pointcutFilterComposite, $trace);
			}
		}

		return $this->parse($pointcutExpression, 'method resources of a policy configuration');
	}

	/**
	 * Parses the security constraints configured for persistence entities
	 *
	 * @param array $entityResourcesTree The tree of all available entity resources
	 * @return array The constraints definition array for all entity resources
	 * @throws InvalidPolicyException
	 */
	public function parseEntityResources(array $entityResourcesTree) {
		$entityResourcesConstraints = array();

		foreach ($entityResourcesTree as $entityType => $entityResources) {
			if (strpos($entityType, '_') !== FALSE) {
				throw new InvalidPolicyException('Entity types in resource definitions must be fully qualified class names, "' . $entityType . '" violates this. Please adjust your Policy.yaml file(s).', 1354708376);
			}
			foreach ($entityResources as $resourceName => $constraintDefinition) {
				if ($constraintDefinition === PolicyService::MATCHER_ANY) {
					$entityResourcesConstraints[$entityType][$resourceName] = PolicyService::MATCHER_ANY;
				} else {
					$entityResourcesConstraints[$entityType][$resourceName] = $this->parseSingleEntityResource($resourceName, $entityResources);
				}
			}
		}

		return $entityResourcesConstraints;
	}

	/**
	 * Walks recursively through the method resources tree.
	 *
	 * @param string $operator The operator
	 * @param string $pointcutExpression The pointcut expression (value of the designator)
	 * @param \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the pointcut filter) will be added to this composite object.
	 * @param array &$trace
	 * @return void
	 * @throws \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
	 */
	protected function parseDesignatorPointcut($operator, $pointcutExpression, \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite $pointcutFilterComposite, array &$trace = array()) {
		if (!isset($this->methodResourcesTree[$pointcutExpression])) {
			throw new \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException('The given resource was not defined: "' . $pointcutExpression . '".', 1222014591);
		}

		$pointcutFilterComposite->addFilter($operator, $this->parseMethodResources($this->methodResourcesTree[$pointcutExpression], array(), $trace));
	}

	/**
	 * Parses the security constraints configured for a single entity resource. If needed
	 * it walks recursively through the entity resources tree array.
	 *
	 * @param string $resourceName The name of the resource to be parsed
	 * @param array $entityResourcesTree The tree of all available resources for one entity
	 * @return array The constraints definition array
	 * @throws \TYPO3\Flow\Security\Exception\NoEntryInPolicyException
	 */
	protected function parseSingleEntityResource($resourceName, array $entityResourcesTree) {
		$expressionParts = preg_split(parent::PATTERN_SPLITBYOPERATOR, $entityResourcesTree[$resourceName], -1, PREG_SPLIT_DELIM_CAPTURE);

		$constraints = array();
		for ($i = 0; $i < count($expressionParts); $i += 2) {
			$operator = ($i > 1 ? $expressionParts[$i - 1] : '&&');

			if (!isset($constraints[$operator])) {
				$constraints[$operator] = array();
			}

			if (preg_match('/\s(==|!=|<=|>=|<|>|in|contains|matches)\s/', $expressionParts[$i]) > 0) {
				$constraints[$operator] = array_merge($constraints[$operator], $this->getRuntimeEvaluationConditionsFromEvaluateString($expressionParts[$i]));
			} else {
				if (!isset($entityResourcesTree[$expressionParts[$i]])) {
					throw new \TYPO3\Flow\Security\Exception\NoEntryInPolicyException('Entity resource "' . $expressionParts[$i] . '" not found in policy.', 1267722067);
				}
				$constraints[$operator]['subConstraints'] = $this->parseSingleEntityResource($expressionParts[$i], $entityResourcesTree);
			}
		}

		return $constraints;
	}
}
