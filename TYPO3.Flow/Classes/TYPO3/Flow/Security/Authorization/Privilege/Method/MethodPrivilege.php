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
use TYPO3\Flow\Aop\Pointcut\PointcutFilter;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;
use TYPO3\Flow\Aop\Pointcut\RuntimeExpressionEvaluator;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use TYPO3\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * A method privilege, able to restrict method calls based on pointcut expressions
 */
class MethodPrivilege extends AbstractPrivilege implements MethodPrivilegeInterface {

	/**
	 * @var array
	 */
	protected static $methodPermissions;

	/**
	 * @var PointcutFilter
	 */
	protected $pointcutFilter;

	/**
	 * @var RuntimeExpressionEvaluator
	 */
	protected $runtimeExpressionEvaluator;

	/**
	 * This object is created very early so we can't rely on AOP for the property injection
	 * This method also takes care of initializing caches and other dependencies.
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->initialize();
	}
	/**
	 * @return void
	 */
	protected function initialize() {
		if ($this->runtimeExpressionEvaluator !== NULL) {
			return;
		}

		/** @var CacheManager $cacheManager */
		$cacheManager = $this->objectManager->get(CacheManager::class);
		$this->runtimeExpressionEvaluator = $this->objectManager->get(RuntimeExpressionEvaluator::class);
		$this->runtimeExpressionEvaluator->injectObjectManager($this->objectManager);

		if (static::$methodPermissions !== NULL) {
			return;
		}
		static::$methodPermissions = $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method')->get('methodPermission');
	}

	/**
	 * Returns TRUE, if this privilege covers the given subject (join point)
	 *
	 * @param PrivilegeSubjectInterface $subject
	 * @return boolean
	 * @throws InvalidPrivilegeTypeException
	 */
	public function matchesSubject(PrivilegeSubjectInterface $subject) {
		if ($subject instanceof MethodPrivilegeSubject === FALSE) {
			throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface" only support subjects of type "TYPO3\Flow\Security\Method\MethodPrivilegeSubject", but we got a subject of type: "%s".', get_class($subject)), 1416241148);
		}

		$this->initialize();
		$joinPoint = $subject->getJoinPoint();

		$methodIdentifier = strtolower($joinPoint->getClassName() . '->' . $joinPoint->getMethodName());

		if (isset(static::$methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()])) {
			if (static::$methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()]['hasRuntimeEvaluations']) {
				if ($this->runtimeExpressionEvaluator->evaluate($this->getCacheEntryIdentifier(), $joinPoint) === FALSE) {
					return FALSE;
				}
			}
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns TRUE, if this privilege covers the given method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return boolean
	 */
	public function matchesMethod($className, $methodName) {
		$this->initialize();

		$methodIdentifier = strtolower($className . '->' . $methodName);
		if (isset(static::$methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()])) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns the pointcut filter composite, matching all methods covered by this privilege
	 *
	 * @return PointcutFilterComposite
	 */
	public function getPointcutFilterComposite() {
		if ($this->pointcutFilter === NULL) {
			/** @var MethodTargetExpressionParser $methodTargetExpressionParser */
			$methodTargetExpressionParser = $this->objectManager->get('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodTargetExpressionParser');
			$this->pointcutFilter = $methodTargetExpressionParser->parse($this->getParsedMatcher(), 'Policy privilege "' . $this->getPrivilegeTargetIdentifier() . '"');
		}

		return $this->pointcutFilter;
	}

}