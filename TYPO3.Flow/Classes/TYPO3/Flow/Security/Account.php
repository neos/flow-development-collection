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

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

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
	 * @var \TYPO3\Party\Domain\Model\AbstractParty
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
	 * @var array<\TYPO3\Flow\Security\Policy\Role>
	 */
	protected $roles = array();

	/**
	 * Upon creation the creationDate property is initialized.
	 */
	public function __construct() {
		$this->creationDate = new \DateTime();
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
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The party object
	 */
	public function getParty() {
		return $this->party;
	}

	/**
	 * Sets the corresponding party for this account
	 *
	 * @param \TYPO3\Party\Domain\Model\AbstractParty $party The party object
	 * @return void
	 */
	public function setParty(\TYPO3\Party\Domain\Model\AbstractParty $party) {
		$this->party = $party;
	}

	/**
	 * Returns the roles this account has assigned
	 *
	 * @return array<\TYPO3\Flow\Security\Policy\Role> The assigned roles
	 */
	public function getRoles() {
		$roles = array();
		foreach ($this->roles as $role) {
			$roles[] = new \TYPO3\Flow\Security\Policy\Role($role);
		}
		return $roles;
	}

	/**
	 * Sets the roles for this account
	 *
	 * @param array $roles An array of TYPO3\Flow\Security\Policy\Role objects
	 * @return void
	 */
	public function setRoles(array $roles) {
		$this->roles = array();
		foreach ($roles as $role) {
			$this->roles[] = (string)$role;
		}
	}

	/**
	 * Return if the account has a certain role
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role
	 * @return boolean
	 */
	public function hasRole(\TYPO3\Flow\Security\Policy\Role $role) {
		return in_array((string) $role, $this->roles, TRUE);
	}

	/**
	 * Adds a role to this account
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role
	 * @return void
	 */
	public function addRole(\TYPO3\Flow\Security\Policy\Role $role) {
		if (!$this->hasRole($role)) {
			$this->roles[] = (string)$role;
		}
	}

	/**
	 * Removes a role from this account
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role
	 * @return void
	 */
	public function removeRole(\TYPO3\Flow\Security\Policy\Role $role) {
		if (($key = array_search((string)$role, $this->roles, TRUE)) !== FALSE) {
			unset($this->roles[$key]);
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
	public function setExpirationDate(\DateTime $expirationDate) {
		$this->expirationDate = $expirationDate;
	}

}
?>
