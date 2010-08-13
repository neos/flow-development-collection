<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Token;

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
 * An authentication token used for simple username and password authentication.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class UsernamePassword implements \F3\FLOW3\Security\Authentication\TokenInterface {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var string
	 */
	protected $authenticationProviderName;

	/**
	 * Current authentication status of this token
	 * @var integer
	 */
	protected $authenticationStatus = self::NO_CREDENTIALS_GIVEN;

	/**
	 * The username/password credentials
	 * @var array
	 * @transient
	 */
	protected $credentials = array('username' => '', 'password' => '');

	/**
	 * @var F3\FLOW3\Security\Account
	 */
	protected $account;

	/**
	 * @var array The set request patterns
	 */
	protected $requestPatterns = NULL;

	/**
	 * The authentication entry point
	 * @var \F3\FLOW3\Security\Authentication\EntryPointInterface
	 */
	protected $entryPoint = NULL;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager The object factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment The current environment object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Returns the name of the authentication provider responsible for this token
	 *
	 * @return string The authentication provider name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationProviderName() {
		return $this->authenticationProviderName;
	}

	/**
	 * Sets the name of the authentication provider responsible for this token
	 *
	 * @param string $authenticationProviderName The authentication provider name
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationProviderName($authenticationProviderName) {
		$this->authenticationProviderName = $authenticationProviderName;
	}

	/**
	 * Returns TRUE if this token is currently authenticated
	 *
	 * @return boolean TRUE if this this token is currently authenticated
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isAuthenticated() {
		return ($this->authenticationStatus === self::AUTHENTICATION_SUCCESSFUL);
	}

	/**
	 * Sets the authentication entry point
	 *
	 * @param \F3\FLOW3\Security\Authentication\EntryPointInterface $entryPoint The authentication entry point
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationEntryPoint(\F3\FLOW3\Security\Authentication\EntryPointInterface $entryPoint) {
		$this->entryPoint = $entryPoint;
	}

	/**
	 * Returns the configured authentication entry point, NULL if none is available
	 *
	 * @return \F3\FLOW3\Security\Authentication\EntryPointInterface The configured authentication entry point, NULL if none is available
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationEntryPoint() {
		return $this->entryPoint;
	}

	/**
	 * Returns TRUE if \F3\FLOW3\Security\RequestPattern were set
	 *
	 * @return boolean True if a \F3\FLOW3\Security\RequestPatternInterface was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRequestPatterns() {
		if ($this->requestPatterns != NULL) return TRUE;
		return FALSE;
	}

	/**
	 * Sets request patterns
	 *
	 * @param array $requestPatterns Array of \F3\FLOW3\Security\RequestPattern to be set
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPatterns(array $requestPatterns) {
		$this->requestPatterns = $requestPatterns;
	}

	/**
	 * Returns an array of set \F3\FLOW3\Security\RequestPatternInterface, NULL if none was set
	 *
	 * @return array Array of set request patterns
	 * @see hasRequestPattern()
	 */
	public function getRequestPatterns() {
		return $this->requestPatterns;
	}

	/**
	 * Updates the username and password credentials from the POST vars, if the POST parameters
	 * are available. Sets the authentication status to REAUTHENTICATION_NEEDED, if credentials have been sent.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function updateCredentials() {
		$postArguments = $this->environment->getRawPostArguments();
		if (isset($postArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::username'])
			&& isset($postArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::password'])) {

			$this->credentials['username'] = $postArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::username'];
			$this->credentials['password'] = $postArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::password'];

			$this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
		}
	}

	/**
	 * Returns the credentials (username and password) of this token.
	 *
	 * @return object $credentials The needed credentials to authenticate this token
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getCredentials() {
		return $this->credentials;
	}

	/**
	 * Returns the account if one is authenticated, NULL otherwise.
	 *
	 * @return F3\FLOW3\Security\Account An account object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAccount() {
		return $this->account;
	}

	/**
	 * Set the (authenticated) account
	 *
	 * @param F3\FLOW3\Security\Account $account An account object
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAccount(\F3\FLOW3\Security\Account $account) {
		$this->account = $account;
	}

	/**
	 * Returns the currently valid roles.
	 *
	 * @return array Array of F3\FLOW3\Security\Authentication\Role objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRoles() {
		if ($this->account !== NULL && $this->isAuthenticated()) return $this->account->getRoles();

		return array();
	}

	/**
	 * Sets the authentication status. Usually called by the responsible \F3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 *
	 * @param integer $authenticationStatus One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, AUTHENTICATION_NEEDED
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @throws F3\FLOW3\Security\Exception\InvalidAuthenticationStatusException
	 */
	public function setAuthenticationStatus($authenticationStatus) {
		if (!in_array($authenticationStatus, array(self::NO_CREDENTIALS_GIVEN, self::WRONG_CREDENTIALS, self::AUTHENTICATION_SUCCESSFUL, self::AUTHENTICATION_NEEDED))) throw new \F3\FLOW3\Security\Exception\InvalidAuthenticationStatusException('Invalid authentication status.', 1237224453);

		$this->authenticationStatus = $authenticationStatus;
	}

	/**
	 * Returns the current authentication status
	 *
	 * @return integer One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, AUTHENTICATION_NEEDED
	 */
	public function getAuthenticationStatus() {
		return $this->authenticationStatus;
	}
}

?>