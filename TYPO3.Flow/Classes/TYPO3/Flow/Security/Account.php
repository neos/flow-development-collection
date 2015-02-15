<?php
namespace TYPO3\Flow\Security;

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
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Party\Domain\Model\AbstractParty;

/**
 * An account model
 *
 * @Flow\Entity
 */
class Account {

	/**
	 * @var string
	 * @Flow\Identity
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 */
	protected $accountIdentifier;

	/**
	 * @var string
	 * @Flow\Identity
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $authenticationProviderName;

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $credentialsSource;

	/**
	 * @var AbstractParty
	 * @ORM\ManyToOne(inversedBy="accounts")
	 */
	protected $party;

	/**
	 * @var \DateTime
	 */
	protected $creationDate;

	/**
	 * @var \DateTime
	 * @ORM\Column(nullable=true)
	 */
	protected $expirationDate;

	/**
	 * @var array of strings
	 * @ORM\Column(type="simple_array", nullable=true)
	 */
	protected $roleIdentifiers = array();

	/**
	 * @Flow\Transient
	 * @var array<Role>
	 */
	protected $roles;

	/**
	 * @Flow\Inject
	 * @var PolicyService
	 */
	protected $policyService;

	/**
	 * Upon creation the creationDate property is initialized.
	 */
	public function __construct() {
		$this->creationDate = new \DateTime();
	}

	/**
	 * Initializes the roles field by fetching the role objects referenced by the roleIdentifiers
	 *
	 * @return void
	 */
	protected function initializeRoles() {
		if ($this->roles !== NULL) {
			return;
		}
		$this->roles = array();
		foreach ($this->roleIdentifiers as $key => $roleIdentifier) {
			// check for and clean up roles no longer available
			if ($this->policyService->hasRole($roleIdentifier)) {
				$this->roles[$roleIdentifier] = $this->policyService->getRole($roleIdentifier);
			} else {
				unset($this->roleIdentifiers[$key]);
			}
		}
	}

	/**
	 * Returns the account identifier
	 *
	 * @return string The account identifier
	 */
	public function getAccountIdentifier() {
		return $this->accountIdentifier;
	}

	/**
	 * Set the account identifier
	 *
	 * @param string $accountIdentifier The account identifier
	 * @return void
	 */
	public function setAccountIdentifier($accountIdentifier) {
		$this->accountIdentifier = $accountIdentifier;
	}

	/**
	 * Returns the authentication provider name this account corresponds to
	 *
	 * @return string The authentication provider name
	 */
	public function getAuthenticationProviderName() {
		return $this->authenticationProviderName;
	}

	/**
	 * Set the authentication provider name this account corresponds to
	 *
	 * @param string $authenticationProviderName The authentication provider name
	 * @return void
	 */
	public function setAuthenticationProviderName($authenticationProviderName) {
		$this->authenticationProviderName = $authenticationProviderName;
	}

	/**
	 * Returns the credentials source
	 *
	 * @return mixed The credentials source
	 */
	public function getCredentialsSource() {
		return $this->credentialsSource;
	}

	/**
	 * Sets the credentials source
	 *
	 * @param mixed $credentialsSource The credentials source
	 * @return void
	 */
	public function setCredentialsSource($credentialsSource) {
		$this->credentialsSource = $credentialsSource;
	}

	/**
	 * Returns the party object this account corresponds to
	 *
	 * @return AbstractParty The party object
	 */
	public function getParty() {
		return $this->party;
	}

	/**
	 * Sets the corresponding party for this account
	 *
	 * @param AbstractParty $party The party object
	 * @return void
	 */
	public function setParty(AbstractParty $party) {
		$this->party = $party;
	}

	/**
	 * Returns the roles this account has assigned
	 *
	 * @return array<Role> The assigned roles, indexed by role identifier
	 */
	public function getRoles() {
		$this->initializeRoles();
		return $this->roles;
	}

	/**
	 * Sets the roles for this account
	 *
	 * @param array<Role> $roles An array of \TYPO3\Flow\Security\Policy\Role objects
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setRoles(array $roles) {
		$this->roleIdentifiers = array();
		$this->roles = array();
		foreach ($roles as $role) {
			if (!$role instanceof Role) {
				throw new \InvalidArgumentException(sprintf('setRoles() only accepts an array of \TYPO3\Flow\Security\Policy\Role instances, given: "%s"', gettype($role)), 1397125997);
			}
			$this->addRole($role);
		}
	}

	/**
	 * Return if the account has a certain role
	 *
	 * @param Role $role
	 * @return boolean
	 */
	public function hasRole(Role $role) {
		$this->initializeRoles();
		return array_key_exists($role->getIdentifier(), $this->roles);
	}

	/**
	 * Adds a role to this account
	 *
	 * @param Role $role
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function addRole(Role $role) {
		if ($role->isAbstract()) {
			throw new \InvalidArgumentException(sprintf('Abstract roles can\'t be assigned to accounts directly, but the role "%s" is marked abstract', $role->getIdentifier()), 1399900657);
		}
		$this->initializeRoles();
		if (!$this->hasRole($role)) {
			$roleIdentifier = $role->getIdentifier();
			$this->roleIdentifiers[] = $roleIdentifier;
			$this->roles[$roleIdentifier] = $role;
		}
	}

	/**
	 * Removes a role from this account
	 *
	 * @param Role $role
	 * @return void
	 */
	public function removeRole(Role $role) {
		$this->initializeRoles();
		if ($this->hasRole($role)) {
			$roleIdentifier = $role->getIdentifier();
			unset($this->roles[$roleIdentifier]);
			$identifierIndex = array_search($roleIdentifier, $this->roleIdentifiers);
			unset($this->roleIdentifiers[$identifierIndex]);
		}
	}

	/**
	 * @return \DateTime
	 */
	public function getCreationDate() {
		return $this->creationDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getExpirationDate() {
		return $this->expirationDate;
	}

	/**
	 * @param \DateTime $expirationDate
	 * @return void
	 */
	public function setExpirationDate(\DateTime $expirationDate = NULL) {
		$this->expirationDate = $expirationDate;
	}
}
