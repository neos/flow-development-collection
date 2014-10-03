<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Method;

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
use TYPO3\Flow\Aop\Builder\ClassNameIndex;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Policy\PolicyService;

/**
 * Pointcut filter which connects the method privileges to the AOP framework
 *
 * @Flow\Scope("singleton")
 */
class MethodPrivilegePointcutFilter implements PointcutFilterInterface {

	/**
	 * @var PointcutFilterComposite[]
	 */
	protected $filters = NULL;

	/**
	 * @var array
	 */
	protected $methodPermissions = array();

	/**
	 * @var VariableFrontend
	 */
	protected $methodPermissionCache;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * This object is created very early so we can't rely on AOP for the property injection
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		/** @var CacheManager $cacheManager */
		$cacheManager = $this->objectManager->get('TYPO3\Flow\Cache\CacheManager');
		$this->methodPermissionCache = $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method');
	}

	/**
	 * @return void
	 */
	public function initializeObject() {
		if ($this->methodPermissionCache->has('methodPermission')) {
			$this->methodPermissions = $this->methodPermissionCache->get('methodPermission');
		}
	}

	/**
	 * Checks if the specified class and method matches against the filter, i.e. if there is a policy entry to intercept this method.
	 * This method also creates a cache entry for every method, to cache the associated roles and privileges.
	 *
	 * @param string $className Name of the class to check the name of
	 * @param string $methodName Name of the method to check the name of
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the names match, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {

		if ($this->filters === NULL) {
			$this->buildPointcutFilters();
		}

		$matches = FALSE;
		/** @var PointcutFilterComposite $filter */
		foreach ($this->filters as $privilegeIdentifier => $filter) {
			if ($filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
				$matches = TRUE;
				$methodIdentifier = strtolower($className . '->' . $methodName);

				if ($filter->hasRuntimeEvaluationsDefinition() === TRUE) {
					$runtimeEvaluationsClosureCode = $filter->getRuntimeEvaluationsClosureCode();
				} else {
					$runtimeEvaluationsClosureCode = FALSE;
				}

				$this->methodPermissions[$methodIdentifier][$privilegeIdentifier]['privilegeMatchesMethod'] = TRUE;
				$this->methodPermissions[$methodIdentifier][$privilegeIdentifier]['runtimeEvaluationsClosureCode'] = $runtimeEvaluationsClosureCode;
			}
		}

		return $matches;
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return FALSE;
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array();
	}

	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param ClassNameIndex $classNameIndex
	 * @return ClassNameIndex
	 */
	public function reduceTargetClassNames(ClassNameIndex $classNameIndex) {
		if ($this->filters === NULL) {
			$this->buildPointcutFilters();
		}

		$result = new ClassNameIndex();
		foreach ($this->filters as $filter) {
			$result->applyUnion($filter->reduceTargetClassNames($classNameIndex));
		}
		return $result;
	}

	/**
	 * Builds the needed pointcut filters for matching the policy privileges
	 *
	 * @return boolean
	 */
	protected function buildPointcutFilters() {
		$this->filters = array();
		/** @var PolicyService $policyService */
		$policyService = $this->objectManager->get('TYPO3\Flow\Security\Policy\PolicyService');
		/** @var MethodPrivilegeInterface[] $methodPrivileges */
		$methodPrivileges = $policyService->getAllPrivilegesByType('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface');
		foreach ($methodPrivileges as $privilege) {
			$this->filters[$privilege->getCacheEntryIdentifier()] = $privilege->getPointcutFilterComposite();
		}
	}

	/**
	 * Save the found matches to the cache.
	 *
	 * @return void
	 */
	public function savePolicyCache() {
		$tags = array('TYPO3_Flow_Aop');
		if (!$this->methodPermissionCache->has('methodPermission')) {
			$this->methodPermissionCache->set('methodPermission', $this->methodPermissions, $tags);
		}
	}
}