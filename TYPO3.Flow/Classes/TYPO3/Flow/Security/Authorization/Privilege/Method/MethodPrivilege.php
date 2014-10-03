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
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use TYPO3\Flow\Security\Authorization\PrivilegeVoteResult;
use TYPO3\Flow\Security\Policy\Role;

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
	 * This method votes on the configured permissions for a given subject
	 *
	 * @param mixed $subject The subject to vote for. This class can only vote for subjects of type JoinPointInterface!
	 * @return PrivilegeVoteResult
	 */
	public static function vote($subject) {
		if ($subject instanceof JoinPointInterface === FALSE) {
			return new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN, sprintf('The method privilege voter can only vote on join points, but we got a subject of type: "%s".', get_class($subject)));
		}

		$securityContext = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Context');

		$effectivePrivilegeIdentifiersWithPermission = array();
		$accessGrants = 0;
		$accessDenies = 0;
		/** @var Role $role */
		foreach ($securityContext->getRoles() as $role) {
			/** @var MethodPrivilegeInterface[] $methodPrivileges */
			$methodPrivileges = $role->getPrivilegesByType('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface');
			/** @var PrivilegeInterface[] $effectivePrivileges */
			$effectivePrivileges = array();
			foreach ($methodPrivileges as $privilege) {
				if ($privilege->matchesJoinpoint($subject)) {
					$effectivePrivileges[] = $privilege;
				}
			}

			foreach ($effectivePrivileges as $effectivePrivilege) {
				$privilegeName = $effectivePrivilege->getPrivilegeTargetIdentifier();
				$parameterStrings = array();
				foreach ($effectivePrivilege->getParameters() as $parameter) {
					$parameterStrings[] = sprintf('%s: "%s"', $parameter->getName(), $parameter->getValue());
				}
				if ($parameterStrings !== array()) {
					$privilegeName .= ' (with parameters: ' . implode(', ', $parameterStrings) . ')';
				}

				$effectivePrivilegeIdentifiersWithPermission[] = sprintf('"%s": %s', $privilegeName, strtoupper($effectivePrivilege->getPermission()));
				if ($effectivePrivilege->isGranted()) {
					$accessGrants ++;
				} elseif ($effectivePrivilege->isDenied()) {
					$accessDenies ++;
				}
			}
		}

		if (count($effectivePrivilegeIdentifiersWithPermission) === 0) {
			$reason = 'No MethodPrivilege matched the join point';
		} else {
			$reason = sprintf('Evaluated following %d privilege target(s): ' . chr(10) . '%s', count($effectivePrivilegeIdentifiersWithPermission), implode(chr(10), $effectivePrivilegeIdentifiersWithPermission));
		}
		if ($accessDenies > 0) {
			return new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_DENY, $reason);
		}
		if ($accessGrants > 0) {
			return new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_GRANT, $reason);
		}

		return new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN, $reason);
	}

	/**
	 * Returns TRUE, if this privilege covers the given join point
	 *
	 * @param JoinPointInterface $joinPoint
	 * @return boolean
	 */
	public function matchesJoinpoint(JoinPointInterface $joinPoint) {
		$this->initialize();

		$methodIdentifier = strtolower($joinPoint->getClassName() . '->' . $joinPoint->getMethodName());

		if (isset($this->methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()])) {
			if ($this->methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()]['runtimeEvaluationsClosureCode'] !== FALSE) {
				// Make object manager usable as closure variable
				/** @noinspection PhpUnusedLocalVariableInspection */
				$objectManager = $this->objectManager;
				eval('$runtimeEvaluator = ' . $this->methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()]['runtimeEvaluationsClosureCode'] . ';');
				/** @noinspection PhpUndefinedVariableInspection */
				if ($runtimeEvaluator->__invoke($joinPoint) === FALSE) {
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