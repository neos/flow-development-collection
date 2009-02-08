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
 * @package FLOW3
 * @subpackage Security
 * @version $Id: F3_FLOW3_Security_Authentication_Token_UsernamePassword.php 1707 2009-01-07 10:37:30Z k-fish $
 */

/**
 * An authentication token used for RSA encrypted username and password authentication.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id: F3_FLOW3_Security_Authentication_Token_UsernamePassword.php 1707 2009-01-07 10:37:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 * @todo here we also need a user details service and an authentication entry point
 */
class RSAUsernamePassword implements \F3\FLOW3\Security\Authentication\TokenInterface {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Indicates wether this token is authenticated
	 * @var boolean
	 */
	protected $authenticationStatus = FALSE;

	/**
	 * The username/password credentials
	 * @var array
	 */
	protected $credentials = array('encryptedUsername' => '', 'encryptedPassword' => '');

	/**
	 * @var \F3\FLOW3\Security\RequestPatternInterface The set request pattern
	 */
	protected $requestPattern = NULL;

	/**
	 * The RSAWalletService
	 * @var \F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface
	 */
	protected $RSAWalletService;

	/**
	 * The current valid keypair UUID for password encryption, NULL if none is valid.
	 * @var UUID
	 */
	protected $passwordKeypairUUID = NULL;

	/**
	 * The current valid keypair UUID for username encryption, NULL if none is valid.
	 * @var UUID
	 */
	protected $usernameKeypairUUID = NULL;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory The object factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
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
	 * Inject the RSAWAlletService
	 *
	 * @param \F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface $RSAWalletService The RSAWalletService
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectRSAWalletService(\F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface $RSAWalletService) {
		$this->RSAWalletService = $RSAWalletService;
	}

	/**
	 * Returns TRUE if this token is currently authenticated
	 *
	 * @return boolean TRUE if this this token is currently authenticated
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isAuthenticated() {
		return $this->authenticationStatus;
	}

	/**
	 * Returns the configured authentication entry point, NULL if none is available
	 *
	 * @return \F3\FLOW3\Security\Authentication\EntryPoint The configured authentication entry point, NULL if none is available
	 */
	public function getAuthenticationEntryPoint() {

	}

	/**
	 * Returns TRUE if a \F3\FLOW3\Security\RequestPattern was set
	 *
	 * @return boolean True if a \F3\FLOW3\Security\RequestPatternInterface was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRequestPattern() {
		if ($this->requestPattern != NULL) return TRUE;
		return FALSE;
	}

	/**
	 * Sets a \F3\FLOW3\Security\RequestPattern
	 *
	 * @param \F3\FLOW3\Security\RequestPatternInterface $requestPattern A request pattern
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPattern(\F3\FLOW3\Security\RequestPatternInterface $requestPattern) {
		$this->requestPattern = $requestPattern;
	}

	/**
	 * Returns the set \F3\FLOW3\Security\RequestPatternInterface, NULL if none was set
	 *
	 * @return \F3\FLOW3\Security\RequestPatternInterface The set request pattern
	 * @see hasRequestPattern()
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRequestPattern() {
		return $this->requestPattern;
	}

	/**
	 * Updates the username and password credentials from the POST vars, if the POST parameters
	 * are available.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function updateCredentials() {
		$POSTArguments = $this->environment->getRawPOSTArguments();

		if (isset($POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedUsername'])) $this->credentials['encryptedUsername'] = $POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedUsername'];
		if (isset($POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedPassword'])) $this->credentials['encryptedPassword'] = $POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedPassword'];
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
	 * Might ask a \F3\FLOW3\Security\Authentication\UserDetailsServiceInterface.
	 *
	 * @return \F3\FLOW3\Security\Authentication\UserDetailsInterface A user details object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo implement this method
	 */
	public function getUserDetails() {

	}

	/**
	 * Returns the currently valid granted authorities. It might ask a \F3\FLOW3\Security\Authentication\UserDetailsServiceInterface.
	 * Note: You have to check isAuthenticated() before you call this method
	 *
	 * @return array Array of \F3\FLOW3\Security\Authentication\GrantedAuthority objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo implement this method, otherwise everbody will be an administrator ;-)
	 */
	public function getGrantedAuthorities() {
		return array($this->objectFactory->create('F3\FLOW3\Security\ACL\Role', 'ADMINISTRATOR'));
	}

	/**
	 * Sets the authentication status. Usually called by the responsible \F3\FLOW3\Security\Authentication\ManagerInterface
	 *
	 * @param boolean $authenticationStatus TRUE if the token ist authenticated, FALSE otherwise
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationStatus($authenticationStatus) {
		$this->authenticationStatus = $authenticationStatus;
	}

	/**
	 * Generates and returns the public key to perform password encryption on the client
	 *
	 * @return \F3\FLOW3\Security\Cryptography\OpenSSLRSAPublicKey
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function generatePublicKeyForPassword() {
		if ($this->passwordKeypairUUID === NULL) {
			$this->passwordKeypairUUID = $this->RSAWalletService->generateNewKeypair(TRUE);
		}

		return $this->RSAWalletService->getPublicKey($this->passwordKeypairUUID);
	}

	/**
	 * Returns the current keypair UUID for password encryption
	 *
	 * @return UUID The current keypair UUID for password encryption
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPasswordKeypairUUID() {
		return $this->passwordKeypairUUID;
	}

	/**
	 * Generates and returns the public key to perform username encryption on the client
	 *
	 * @return \F3\FLOW3\Security\Cryptography\OpenSSLRSAPublicKey
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function generatePublicKeyForUsername() {
		if ($this->usernameKeypairUUID === NULL) {
			$this->usernameKeypairUUID = $this->RSAWalletService->generateNewKeypair(FALSE);
		}

		return $this->RSAWalletService->getPublicKey($this->usernameKeypairUUID);
	}

	/**
	 * Returns the current keypair UUID for username encryption
	 *
	 * @return UUID The current keypair UUID for username encryption
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getUsernameKeypairUUID() {
		return $this->usernameKeypairUUID;
	}

	/**
	 * Invalidates the current keypair (called by the authentication provider)
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invalidateCurrentKeypairs() {
		$this->RSAWalletService->destroyKeypair($this->passwordKeypairUUID);
		$this->RSAWalletService->destroyKeypair($this->usernameKeypairUUID);
		unset($this->passwordKeypairUUID);
		unset($this->usernameKeypairUUID);
	}

	/**
	 * Prepare this object for serialization
	 *
	 * @return array Names of the instance variables to serialize
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __sleep() {
		return array('authenticationStatus', 'credentials', 'requestPattern', 'passwordKeypairUUID', 'usernameKeypairUUID');
	}
}

?>