<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An account model
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
class Account {

	/**
	 * @var string
	 * @identity
	 * @validate NotEmpty, StringLength(minimum = 1, maximum = 255)
	 */
	protected $accountIdentifier;

	/**
	 * @var string
	 * @identity
	 * @validate NotEmpty
	 */
	protected $authenticationProviderName;

	/**
	 * @var string
	 */
	protected $credentialsSource;

	/**
	 * @var F3\Party\Domain\Model\Party
	 */
	protected $party;

	/**
	 * Array of F3\FLOW3\Security\Policy\Role objects
	 * @var array
	 */
	protected $roles = array();

	/**
	 * Returns the account identifier
	 *
	 * @return string The account identifier
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAccountIdentifier() {
		return $this->accountIdentifier;
	}

	/**
	 * Set the account identifier
	 *
	 * @param string $accountIdentifier The account identifier
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAccountIdentifier($accountIdentifier) {
		$this->accountIdentifier = $accountIdentifier;
	}

	/**
	 * Returns the authenitcation provider name this account corresponds to
	 *
	 * @return string The authentication provider name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationProviderName() {
		return $this->authenticationProviderName;
	}

	/**
	 * Set the authentication provider name this account corresponds to
	 *
	 * @param string $authenticationProviderName The authentication provider name
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationProviderName($authenticationProviderName) {
		$this->authenticationProviderName = $authenticationProviderName;
	}

	/**
	 * Returns the credentials source
	 *
	 * @return mixed The credentials source
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getCredentialsSource() {
		return $this->credentialsSource;
	}

	/**
	 * Sets the credentials source
	 *
	 * @param mixed $credentialsSource The credentials source
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setCredentialsSource($credentialsSource) {
		$this->credentialsSource = $credentialsSource;
	}

	/**
	 * Returns the user object this account corresponds to
	 *
	 * @return F3\Party\Domain\Model\Party The party object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getParty() {
		return $this->party;
	}

	/**
	 * Sets the corresponding user for this account
	 *
	 * @param F3\Party\Domain\Model\Party $party The party object
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setParty(\F3\Party\Domain\Model\Party $party) {
		$this->party = $party;
	}

	/**
	 * Returns the roles this account has assigned
	 *
	 * @return array The assigned roles
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRoles() {
		return $this->roles;
	}

	/**
	 * Sets the roles for this account
	 *
	 * @param array $roles An array of F3\FLOW3\Security\Policy\Role objects
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setRoles(array $roles) {
		$this->roles = $roles;
	}
}
?>