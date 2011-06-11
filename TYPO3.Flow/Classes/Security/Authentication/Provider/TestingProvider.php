<?php
namespace F3\FLOW3\Security\Authentication\Provider;

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
 * A singleton authentication provider for functional tests with
 * mockable authentication.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class TestingProvider implements \F3\FLOW3\Security\Authentication\AuthenticationProviderInterface {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \F3\FLOW3\Security\Account
	 */
	protected $account;

	/**
	 * @var int
	 */
	protected $authenticationStatus = \F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN;

	/**
	 * Constructor
	 *
	 * @param string $name The name of this authentication provider
	 * @param array $options Additional configuration options
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($name, array $options = array()) {
		$this->name = $name;
	}

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticate(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		if ($authenticationToken->getAuthenticationProviderName() === $this->name) return TRUE;
		return FALSE;
	}

	/**
	 * Returns the classnames of the tokens this provider is responsible for.
	 *
	 * @return string The classname of the token this provider is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getTokenClassNames() {
		return array('F3\FLOW3\Security\Authentication\Token\TestingToken');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function authenticate(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		$authenticationToken->setAuthenticationStatus($this->authenticationStatus);
		if ($this->authenticationStatus === \F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAccount($this->account);
		} else {
			$authenticationToken->setAccount(NULL);
		}
	}

	/**
	 * Set the account that will be authenticated
	 *
	 * @param \F3\FLOW3\Security\Account $account
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setAccount($account) {
		$this->account = $account;
	}

	/**
	 * Set the authentication status for authentication
	 *
	 * @param int $authenticationStatus
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setAuthenticationStatus($authenticationStatus) {
		$this->authenticationStatus = $authenticationStatus;
	}

	/**
	 * Set the provider name
	 *
	 * @param string $name
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Reset the authentication status and account
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function reset() {
		$this->account = NULL;
		$this->authenticationStatus = \F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN;
	}
}

?>