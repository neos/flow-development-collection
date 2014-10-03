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
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * A role. These roles can be structured in a tree.
 */
class Role {

	/**
	 * The identifier of this role
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * The name of this role (without package key)
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The package key this role belongs to (extracted from the identifier)
	 *
	 * @var string
	 */
	protected $packageKey;

	/**
	 * Whether or not the role is "abstract", meaning it can't be assigned to accounts directly but only serves as a "template role" for other roles to inherit from
	 *
	 * @var boolean
	 */
	protected $abstract = FALSE;

	/**
	 * @Flow\Transient
	 * @var Role[]
	 */
	protected $parentRoles;

	/**
	 * @var PrivilegeInterface[]
	 */
	protected $privileges = array();

	/**
	 * @param string $identifier The fully qualified identifier of this role (Vendor.Package:Role)
	 * @param Role[] $parentRoles
	 * @throws \InvalidArgumentException
	 */
	public function __construct($identifier, array $parentRoles = array()) {
		if (!is_string($identifier)) {
			throw new \InvalidArgumentException('The role identifier must be a string, "' . gettype($identifier) . '" given. Please check the code or policy configuration creating or defining this role.', 1296509556);
		}
		if (preg_match('/^[\w]+((\.[\w]+)*\:[\w]+)?$/', $identifier) !== 1) {

		}
		if (preg_match('/^([\w]+(?:\.[\w]+)*)\:([\w]+)+$/', $identifier, $matches) !== 1) {
			throw new \InvalidArgumentException('The role identifier must follow the pattern "Vendor.Package:RoleName", but "' . $identifier . '" was given. Please check the code or policy configuration creating or defining this role.', 1365446549);
		}
		$this->identifier = $identifier;
		$this->packageKey = $matches[1];
		$this->name = $matches[2];
		$this->parentRoles = $parentRoles;
	}

	/**
	 * Returns the fully qualified identifier of this role
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * The key of the package that defines this role.
	 *
	 * @return string
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * The name of this role, being the identifier without the package key.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param boolean $abstract
	 * @return void
	 */
	public function setAbstract($abstract) {
		$this->abstract = $abstract;
	}

	/**
	 * Whether or not this role is "abstract", meaning it can't be assigned to accounts directly but only serves as a "template role" for other roles to inherit from
	 *
	 * @return boolean
	 */
	public function isAbstract() {
		return $this->abstract;
	}

	/**
	 * Assign parent roles to this role.
	 *
	 * @param Role[] $parentRoles indexed by role identifier
	 * @return void
	 */
	public function setParentRoles(array $parentRoles) {
		$this->parentRoles = array();
		foreach ($parentRoles as $parentRole) {
			$this->addParentRole($parentRole);
		}
	}

	/**
	 * Returns an array of all directly assigned parent roles.
	 *
	 * @return Role[] Array of direct parent roles, indexed by role identifier
	 */
	public function getParentRoles() {
		return $this->parentRoles;
	}

	/**
	 * Returns all (directly and indirectly reachable) parent roles for the given role.
	 *
	 * @return Role[] Array of parent roles, indexed by role identifier
	 */
	public function getAllParentRoles() {
		$result = array();

		foreach ($this->parentRoles as $parentRoleIdentifier => $currentParentRole) {
			if (isset($result[$parentRoleIdentifier])) {
				continue;
			}
			$result[$parentRoleIdentifier] = $currentParentRole;

			$currentGrandParentRoles = $currentParentRole->getAllParentRoles();
			foreach ($currentGrandParentRoles as $currentGrandParentRoleIdentifier => $currentGrandParentRole) {
				if (!isset($result[$currentGrandParentRoleIdentifier])) {
					$result[$currentGrandParentRoleIdentifier] = $currentGrandParentRole;
				}
			}
		}

		return $result;
	}

	/**
	 * Add a (direct) parent role to this role.
	 *
	 * @param Role $parentRole
	 * @return void
	 */
	public function addParentRole(Role $parentRole) {
		if (!$this->hasParentRole($parentRole)) {
			$parentRoleIdentifier = $parentRole->getIdentifier();
			$this->parentRoles[$parentRoleIdentifier] = $parentRole;
		}
	}

	/**
	 * Returns TRUE if the given role is a directly assigned parent of this role.
	 *
	 * @param Role $role
	 * @return boolean
	 */
	public function hasParentRole(Role $role) {
		return isset($this->parentRoles[$role->getIdentifier()]);
	}

	/**
	 * Assign privileges to this role.
	 *
	 * @param PrivilegeInterface[] $privileges
	 * @return void
	 */
	public function setPrivileges(array $privileges) {
		$this->privileges = $privileges;
	}

	/**
	 * @return PrivilegeInterface[] Array of privileges assigned to this role
	 */
	public function getPrivileges() {
		return $this->privileges;
	}

	/**
	 * @param string $className Fully qualified name of the Privilege class to filter for
	 * @return PrivilegeInterface[]
	 */
	public function getPrivilegesByType($className) {
		$privileges = array();
		foreach ($this->privileges as $privilege) {
			if ($privilege instanceof $className) {
				$privileges[] = $privilege;
			}
		}
		return $privileges;
	}

	/**
	 * @param string $privilegeTargetIdentifier
	 * @param array $privilegeParameters
	 * @return PrivilegeInterface the matching privilege or NULL if no privilege exists for the given constraints
	 */
	public function getPrivilegeForTarget($privilegeTargetIdentifier, array $privilegeParameters = array()) {
		foreach ($this->privileges as $privilege) {
			if ($privilege->getPrivilegeTargetIdentifier() !== $privilegeTargetIdentifier) {
				continue;
			}
			if (array_diff_assoc($privilege->getParameters(), $privilegeParameters) !== array()) {
				continue;
			}
			return $privilege;
		}
		return NULL;
	}

	/**
	 * Add a privilege to this role.
	 *
	 * @param PrivilegeInterface $privilege
	 * @return void
	 */
	public function addPrivilege($privilege) {
		$this->privileges[] = $privilege;
	}

	/**
	 * Returns the string representation of this role (the identifier)
	 *
	 * @return string the string representation of this role
	 */
	public function __toString() {
		return $this->identifier;
	}
}
