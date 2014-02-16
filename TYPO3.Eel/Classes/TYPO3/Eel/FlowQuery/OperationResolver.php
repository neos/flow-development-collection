<?php
namespace TYPO3\Eel\FlowQuery;

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
 * FlowQuery Operation Resolver
 *
 * @Flow\Scope("singleton")
 */
class OperationResolver implements OperationResolverInterface {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * 2-dimensional array of registered operations:
	 * shortOperationName => priority => operation class name
	 *
	 * @var array
	 */
	protected $operations = array();

	/**
	 * associative array of registered final operations:
	 * shortOperationName => shortOperationName
	 *
	 * @var array
	 */
	protected $finalOperationNames = array();

	/**
	 * Initializer, building up $this->operations and $this->finalOperationNames
	 */
	public function initializeObject() {
		$operationsAndFinalOperationNames = static::buildOperationsAndFinalOperationNames($this->objectManager);
		$this->operations = $operationsAndFinalOperationNames[0];
		$this->finalOperationNames = $operationsAndFinalOperationNames[1];
	}

	/**
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of sorted operations and array of final operation names
	 * @Flow\CompileStatic
	 */
	static public function buildOperationsAndFinalOperationNames($objectManager) {
		$operations = array();
		$finalOperationNames = array();

		$reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		$operationClassNames = $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Eel\FlowQuery\OperationInterface');
		/** @var $operationClassName OperationInterface */
		foreach ($operationClassNames as $operationClassName) {
			$shortOperationName = $operationClassName::getShortName();
			$operationPriority = $operationClassName::getPriority();
			$isFinalOperation = $operationClassName::isFinal();

			if (!isset($operations[$shortOperationName])) {
				$operations[$shortOperationName] = array();
			}

			if (isset($operations[$shortOperationName][$operationPriority])) {
				throw new FlowQueryException(sprintf('Operation with name "%s" and priority %s is already defined in class %s, and the class %s has the same priority and name.', $shortOperationName, $operationPriority, $operations[$shortOperationName][$operationPriority], $operationClassName), 1332491678);
			}
			$operations[$shortOperationName][$operationPriority] = $operationClassName;

			if ($isFinalOperation) {
				$finalOperationNames[$shortOperationName] = $shortOperationName;
			}
		}

		foreach ($operations as &$operation) {
			krsort($operation, SORT_NUMERIC);
		}

		return array($operations, $finalOperationNames);
	}

	/**
	 * @param string $operationName
	 * @return boolean TRUE if $operationName is final
	 */
	public function isFinalOperation($operationName) {
		return isset($this->finalOperationNames[$operationName]);
	}

	/**
	 * Resolve an operation, taking runtime constraints into account.
	 *
	 * @param string      $operationName
	 * @param array|mixed $context
	 * @throws FlowQueryException
	 * @return OperationInterface the resolved operation
	 */
	public function resolveOperation($operationName, $context) {
		if (!isset($this->operations[$operationName])) {
			throw new FlowQueryException('Operation "' . $operationName . '" not found.', 1332491837);
		}

		foreach ($this->operations[$operationName] as $operationClassName) {
			$operation = $this->objectManager->get($operationClassName);
			if ($operation->canEvaluate($context)) {
				return $operation;
			}
		}
		throw new FlowQueryException('No operation which satisfies the runtime constraints found for "' . $operationName . '".', 1332491864);
	}

	/**
	 * @param string $operationName
	 * @return boolean
	 */
	public function hasOperation($operationName) {
		return isset($this->operations[$operationName]);
	}

}
