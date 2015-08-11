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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Flow\Utility\Now;
use TYPO3\Flow\Security\Exception as SecurityException;

/**
 * An account model
 *
 * @Flow\Entity
 * @api
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
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var Now
	 */
	protected $now;

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
	 * @api
	 */
	public function getAccountIdentifier() {
		return $this->accountIdentifier;
	}

	/**
	 * Set the account identifier
	 *
	 * @param string $accountIdentifier The account identifier
	 * @return void
	 * @api
	 */
	public function setAccountIdentifier($accountIdentifier) {
		$this->accountIdentifier = $accountIdentifier;
	}

	/**
	 * Returns the authentication provider name this account corresponds to
	 *
	 * @return string The authentication provider name
	 * @api
	 */
	public function getAuthenticationProviderName() {
		return $this->authenticationProviderName;
	}

	/**
	 * Set the authentication provider name this account corresponds to
	 *
	 * @param string $authenticationProviderName The authentication provider name
	 * @return void
	 * @api
	 */
	public function setAuthenticationProviderName($authenticationProviderName) {
		$this->authenticationProviderName = $authenticationProviderName;
	}

	/**
	 * Returns the credentials source
	 *
	 * @return mixed The credentials source
	 * @api
	 */
	public function getCredentialsSource() {
		return $this->credentialsSource;
	}

	/**
	 * Sets the credentials source
	 *
	 * @param mixed $credentialsSource The credentials source
	 * @return void
	 * @api
	 */
	public function setCredentialsSource($credentialsSource) {
		$this->credentialsSource = $credentialsSource;
	}

	/**
	 * Returns the party object this account corresponds to
	 *
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The party object
	 * @deprecated since 3.0 something like a party is not attached to the account directly anymore. Fetch your user/party/organization etc. instance on your own using Domain Services or Repositories (see https://jira.typo3.org/browse/FLOW-5)
	 * @throws SecurityException
	 */
	public function getParty() {
		if ($this->accountIdentifier === NULL || $this->accountIdentifier === '') {
			throw new SecurityException('The account identifier for the account where the party is tried to be got is not yet set. Make sure that you set the account identifier prior to calling getParty().', 1397747246);
		}
		if (!$this->objectManager->isRegistered('TYPO3\Party\Domain\Service\PartyService')) {
			throw new SecurityException('The \TYPO3\Party\Domain\Service\PartyService is not available. When using the obsolete method \TYPO3\Flow\Security\Account::getParty, make sure the package TYPO3.Party is installed.', 1397747288);
		}
		/** @var \TYPO3\Party\Domain\Service\PartyService $partyService */
		$partyService = $this->objectManager->get('TYPO3\Party\Domain\Service\PartyService');
		return $partyService->getAssignedPartyOfAccount($this);
	}

	/**
	 * Sets the corresponding party for this account
	 *
	 * @param \TYPO3\Party\Domain\Model\AbstractParty $party The party object
	 * @deprecated since 3.0 something like a party is not attached to the account directly anymore. Fetch your user/party/organization etc. instance on your own using Domain Services or Repositories (see https://jira.typo3.org/browse/FLOW-5)
	 * @throws SecurityException
	 */
	public function setParty($party) {
		if ($this->accountIdentifier === NULL || $this->accountIdentifier === '') {
			throw new SecurityException('The account identifier for the account where the party is tried to be set is not yet set. Make sure that you set the account identifier prior to calling setParty().', 1397745354);
		}
		if (!$this->objectManager->isRegistered('TYPO3\Party\Domain\Service\PartyService')) {
			throw new SecurityException('The \TYPO3\Party\Domain\Service\PartyService is not available. When using the obsolete method \TYPO3\Flow\Security\Account::getParty, make sure the package TYPO3.Party is installed.', 1397747413);
		}
		/** @var \TYPO3\Party\Domain\Service\PartyService $partyService */
		$partyService = $this->objectManager->get('TYPO3\Party\Domain\Service\PartyService');
		$partyService->assignAccountToParty($this, $party);
	}

	/**
	 * Returns the roles this account has assigned
	 *
	 * @return array<Role> The assigned roles, indexed by role identifier
	 * @api
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
	 * @api
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
	 * @api
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
	 * @api
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
	 * @api
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
	 * Returns the date on which this account has been created.
	 *
	 * @return \DateTime
	 * @api
	 */
	public function getCreationDate() {
		return $this->creationDate;
	}

	/**
	 * Returns the date on which this account has expired or will expire. If no expiration date has been set, NULL
	 * is returned.
	 *
	 * @return \DateTime
	 * @api
	 */
	public function getExpirationDate() {
		return $this->expirationDate;
	}

	/**
	 * Sets the date on which this account will become inactive
	 *
	 * @param \DateTime $expirationDate
	 * @return void
	 * @api
	 */
	public function setExpirationDate(\DateTime $expirationDate = NULL) {
		$this->expirationDate = $expirationDate;
	}

	/**
	 * Returns TRUE if it is currently allowed to use this account for authentication.
	 * Returns FALSE if the account has expired.
	 *
	 * @return boolean
	 * @api
	 */
	public function isActive() {
		return ($this->expirationDate === NULL || $this->expirationDate > $this->now);
	}
}
