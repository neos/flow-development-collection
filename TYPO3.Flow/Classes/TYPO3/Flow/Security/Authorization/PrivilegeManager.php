<?php
namespace TYPO3\Flow\Security\Authorization;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Policy\Role;

/**
 * An access decision voter manager
 *
 * @Flow\Scope("singleton")
 */
class PrivilegeManager implements PrivilegeManagerInterface {

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * If set to TRUE access will be granted for objects where all voters abstain from decision.
	 *
	 * @Flow\InjectConfiguration("security.authorization.allowAccessIfAllVotersAbstain")
	 * @var boolean
	 */
	protected $allowAccessIfAllAbstain = FALSE;

	/**
	 * @param ObjectManagerInterface $objectManager The object manager
	 * @param Context $securityContext The current security context
	 */
	public function __construct(ObjectManagerInterface $objectManager, Context $securityContext) {
		$this->objectManager = $objectManager;
		$this->securityContext = $securityContext;
	}

	/**
	 * Returns TRUE, if the given privilege type is granted for the given subject based
	 * on the current security context.
	 *
	 * @param string $privilegeType The type of privilege that should be evaluated
	 * @param mixed $subject The subject to check privileges for
	 * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
	 * @return boolean
	 */
	public function isGranted($privilegeType, $subject, &$reason = '') {
		return $this->isGrantedForRoles($this->securityContext->getRoles(), $privilegeType, $subject, $reason);
	}

	/**
	 * Returns TRUE, if the given privilege type would be granted for the given roles and subject
	 *
	 * @param array<Role> $roles The roles that should be evaluated
	 * @param string $privilegeType The type of privilege that should be evaluated
	 * @param mixed $subject The subject to check privileges for
	 * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
	 * @return boolean
	 */
	public function isGrantedForRoles(array $roles, $privilegeType, $subject, &$reason = '') {
		$effectivePrivilegeIdentifiersWithPermission = array();
		$accessGrants = 0;
		$accessDenies = 0;
		$accessAbstains = 0;
		/** @var Role $role */
		foreach ($roles as $role) {
			/** @var PrivilegeInterface[] $availablePrivileges */
			$availablePrivileges = $role->getPrivilegesByType($privilegeType);
			/** @var PrivilegeInterface[] $effectivePrivileges */
			$effectivePrivileges = array();
			foreach ($availablePrivileges as $privilege) {
				if ($privilege->matchesSubject($subject)) {
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
				} else {
					$accessAbstains ++;
				}
			}
		}

		if (count($effectivePrivilegeIdentifiersWithPermission) === 0) {
			$reason = sprintf('No privilege of type "%s" matched.', $privilegeType);
			return TRUE;
		} else {
			$reason = sprintf('Evaluated following %d privilege target(s):' . chr(10) . '%s' . chr(10) . '(%d granted, %d denied, %d abstained)', count($effectivePrivilegeIdentifiersWithPermission), implode(chr(10), $effectivePrivilegeIdentifiersWithPermission), $accessGrants, $accessDenies, $accessAbstains);
		}
		if ($accessDenies > 0) {
			return FALSE;
		}
		if ($accessGrants > 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns TRUE if access is granted on the given privilege target in the current security context
	 *
	 * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
	 * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
	 * @return boolean TRUE if access is granted, FALSE otherwise
	 */
	public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = array()) {
		return $this->isPrivilegeTargetGrantedForRoles($this->securityContext->getRoles(), $privilegeTargetIdentifier, $privilegeParameters);
	}

	/**
	 * Returns TRUE if access is granted on the given privilege target in the current security context
	 *
	 * @param array<Role> $roles The roles that should be evaluated
	 * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
	 * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
	 * @return boolean TRUE if access is granted, FALSE otherwise
	 */
	public function isPrivilegeTargetGrantedForRoles(array $roles, $privilegeTargetIdentifier, array $privilegeParameters = array()) {
		$privilegeFound = FALSE;
		$accessGrants = 0;
		$accessDenies = 0;
		/** @var Role $role */
		foreach ($roles as $role) {
			$privilege = $role->getPrivilegeForTarget($privilegeTargetIdentifier, $privilegeParameters);
			if ($privilege === NULL) {
				continue;
			}

			$privilegeFound = TRUE;

			if ($privilege->isGranted()) {
				$accessGrants++;
			} elseif ($privilege->isDenied()) {
				$accessDenies++;
			}
		}

		if ($accessDenies === 0 && $accessGrants > 0) {
			return TRUE;
		}

		if ($accessDenies === 0 && $accessGrants === 0 && $privilegeFound === TRUE && $this->allowAccessIfAllAbstain === TRUE) {
			return TRUE;
		}

		return FALSE;
	}
}
