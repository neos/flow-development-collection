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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Aop\Pointcut\PointcutFilter;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use TYPO3\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * A method privilege, able to restrict method calls based on pointcut expressions
 */
class MethodPrivilege extends AbstractPrivilege implements MethodPrivilegeInterface {

	/**
	 * @var array
	 */
	protected $methodPermissions;

	/**
	 * @var PointcutFilter
	 */
	protected $pointcutFilter;

	/**
	 * @return void
	 */
	protected function initialize() {
		if ($this->methodPermissions !== NULL) {
			return;
		}
		/** @var CacheManager $cacheManager */
		$cacheManager = $this->objectManager->get('TYPO3\Flow\Cache\CacheManager');
		$this->methodPermissions = $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method')->get('methodPermission');
	}

	/**
	 * Returns TRUE, if this privilege covers the given subject (join point)
	 *
	 * @param mixed $subject
	 * @return boolean
	 * @throws InvalidPrivilegeTypeException
	 */
	public function matchesSubject($subject) {
		if ($subject instanceof JoinPointInterface === FALSE) {
			throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface" only support subjects of type "TYPO3\Flow\Aop\JoinPointInterface", but we got a subject of type: "%s".', get_class($subject)), 1416241148);
		}

		$this->initialize();

		$methodIdentifier = strtolower($subject->getClassName() . '->' . $subject->getMethodName());

		if (isset($this->methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()])) {
			if ($this->methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()]['runtimeEvaluationsClosureCode'] !== FALSE) {
				// Make object manager usable as closure variable
				/** @noinspection PhpUnusedLocalVariableInspection */
				$objectManager = $this->objectManager;
				eval('$runtimeEvaluator = ' . $this->methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()]['runtimeEvaluationsClosureCode'] . ';');
				/** @noinspection PhpUndefinedVariableInspection */
				if ($runtimeEvaluator->__invoke($subject) === FALSE) {
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
		if (isset($this->methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()])) {
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