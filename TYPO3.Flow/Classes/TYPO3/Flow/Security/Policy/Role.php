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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A role. These roles can be structured in a tree.
 *
 * @Flow\Entity
 */
class Role {

	/**
	 * @var string
	 */
	const SOURCE_SYSTEM = 'system';
	const SOURCE_POLICY = 'policy';
	const SOURCE_USER = 'user';

	/**
	 * The identifier of this role
	 *
	 * @var string
	 * @Flow\Identity
	 * @ORM\Id
	 */
	protected $identifier;

	/**
	 * @var string
	 * @Flow\Transient
	 */
	protected $name;

	/**
	 * @var string
	 * @Flow\Transient
	 */
	protected $packageKey;

	/**
	 * One of the SOURCE_* constants, recording where a role comes from (policy file,
	 * user created).
	 *
	 * @var string
	 * @ORM\Column(length = 6)
	 */
	protected $sourceHint;

	/**
	 * @var \Doctrine\Common\Collections\Collection<\TYPO3\Flow\Security\Policy\Role>
	 * @ORM\ManyToMany
	 * @ORM\JoinTable(inverseJoinColumns={@ORM\JoinColumn(name="parent_role")})
	 */
	protected $parentRoles;

	/**
	 * Constructor.
	 *
	 * @param string $identifier The fully qualified identifier of this role (Vendor.Package:Role)
	 * @param string $sourceHint One of the SOURCE_* constants, indicating where a role comes from
	 * @throws \InvalidArgumentException
	 */
	public function __construct($identifier, $sourceHint = self::SOURCE_USER) {
		if (!is_string($identifier)) {
			throw new \InvalidArgumentException('The role identifier must be a string, "' . gettype($identifier) . '" given. Please check the code or policy configuration creating or defining this role.', 1296509556);
		}
		if (preg_match('/^[\w]+((\.[\w]+)*\:[\w]+)?$/', $identifier) !== 1) {
			throw new \InvalidArgumentException('The role identifier must follow the pattern "Vendor.Package:RoleName", but "' . $identifier . '" was given. Please check the code or policy configuration creating or defining this role.', 1365446549);
		}
		if (!in_array($sourceHint, array(self::SOURCE_POLICY, self::SOURCE_SYSTEM, self::SOURCE_USER))) {
			throw new \InvalidArgumentException('The source hint of a role must be one of the built-in SOURCE_* constants.', 1365446550);
		}

		$this->identifier = $identifier;
		$this->sourceHint = $sourceHint;
		$this->parentRoles = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * Initialize the object - sets name and packageKey properties.
	 *
	 * @param integer $initializationCause
	 * @return void
	 */
	public function initializeObject() {
		$this->setNameAndPackageKey();
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
	 * Returns the string representation of this role (the identifier)
	 *
	 * @return string the string representation of this role
	 */
	public function __toString() {
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
	 * Returns one of the SOURCE_* constants, recording where a role comes from
	 * (policy file, user created).
	 *
	 * @return string
	 */
	public function getSourceHint() {
		return $this->sourceHint;
	}

	/**
	 * Assign parent roles to this role.
	 *
	 * @param array<\TYPO3\Flow\Security\Policy\Role> $parentRoles
	 * @return void
	 */
	public function setParentRoles(array $parentRoles) {
		$this->parentRoles->clear();
		foreach ($parentRoles as $role) {
			$this->parentRoles->add($role);
		}
	}

	/**
	 * Returns an array of all directly assigned parent roles.
	 *
	 * @return array<\TYPO3\Flow\Security\Policy\Role> Array of direct parent roles, indexed by role identifier
	 */
	public function getParentRoles() {
		$roles = array();
		foreach ($this->parentRoles->toArray() as $role) {
			$roles[$role->getIdentifier()] = $role;
		}
		return $roles;
	}

	/**
	 * Add a (direct) parent role to this role.
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role
	 * @return void
	 */
	public function addParentRole(\TYPO3\Flow\Security\Policy\Role $role) {
		if (!$this->parentRoles->contains($role)) {
			$this->parentRoles->add($role);
		}
	}

	/**
	 * Returns TRUE if the given role is a directly assigned parent of this role.
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role
	 * @return boolean
	 */
	public function hasParentRole(\TYPO3\Flow\Security\Policy\Role $role) {
		return $this->parentRoles->contains($role);
	}

	/**
	 * Returns TRUE if this roles has any directly assigned parent roles.
	 *
	 * @return boolean
	 */
	public function hasParentRoles() {
		return $this->parentRoles->isEmpty() === FALSE;
	}

	/**
	 * Sets name and packageKey from the identifier.
	 *
	 * @return void
	 */
	protected function setNameAndPackageKey() {
		if (preg_match('/^([\w]+(?:\.[\w]+)*)\:([\w]+)+$/', $this->identifier, $matches) === 1) {
			$this->packageKey = $matches[1];
			$this->name = $matches[2];
		} else {
			$this->name = $this->identifier;
		}
	}
}

?>