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
	 * Current authentication status of this token
	 * @var integer
	 */
	protected $authenticationStatus = self::NO_CREDENTIALS_GIVEN;

	/**
	 * The username/password credentials
	 * @var array
	 */
	protected $credentials = array('encryptedUsername' => '', 'encryptedPassword' => '');

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
		return ($this->authenticationStatus === self::AUTHENTICATION_SUCCESSFUL);
	}

	/**
	 * Sets the authentication entry point
	 *
	 * @param \F3\FLOW3\Security\Authentication\EntryPointInterface $entryPoint The authentication entry point
	 * @return void
	 */
	public function setAuthenticationEntryPoint(\F3\FLOW3\Security\Authentication\EntryPointInterface $entryPoint) {
		$this->entryPoint = $entryPoint;
	}

	/**
	 * Returns the configured authentication entry point, NULL if none is available
	 *
	 * @return \F3\FLOW3\Security\Authentication\EntryPointInterface The configured authentication entry point, NULL if none is available
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
		$POSTArguments = $this->environment->getRawPOSTArguments();

		if (isset($POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedUsername'])
			&& isset($POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedPassword'])) {

			$this->credentials['encryptedUsername'] = $POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedUsername'];
			$this->credentials['encryptedPassword'] = $POSTArguments['F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedPassword'];

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
	 * @param integer $authenticationStatus One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, AUTHENTICATION_NEEDED
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @throws F3\FLOW3\Security\Exception\InvalidAuthenticationStatus
	 */
	public function setAuthenticationStatus($authenticationStatus) {
		if (!in_array($authenticationStatus, array(self::NO_CREDENTIALS_GIVEN, self::WRONG_CREDENTIALS, self::AUTHENTICATION_SUCCESSFUL, self::AUTHENTICATION_NEEDED))) throw new \F3\FLOW3\Security\Exception\InvalidAuthenticationStatus('Invalid authentication status.', 1237224453);

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

	/**
	 * Generates and returns the public key to perform password encryption on the client
	 *
	 * @return \F3\FLOW3\Security\Cryptography\OpenSSLRSAPublicKey
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function generatePublicKeyForPassword() {
		try {
			return $this->RSAWalletService->getPublicKey($this->passwordKeypairUUID);
		} catch (\F3\FLOW3\Security\Exception\InvalidKeyPairID $e) {}

		$this->passwordKeypairUUID = $this->RSAWalletService->generateNewKeypair(TRUE);
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
		try {
			return $this->RSAWalletService->getPublicKey($this->usernameKeypairUUID);
		} catch (\F3\FLOW3\Security\Exception\InvalidKeyPairID $e) {}

		$this->usernameKeypairUUID = $this->RSAWalletService->generateNewKeypair(FALSE);
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
		try {
			$this->RSAWalletService->destroyKeypair($this->passwordKeypairUUID);
			$this->RSAWalletService->destroyKeypair($this->usernameKeypairUUID);
		} catch (\F3\FLOW3\Security\Exception\InvalidKeyPairID $e) {}

		$this->passwordKeypairUUID = NULL;
		$this->usernameKeypairUUID = NULL;
	}

	/**
	 * Prepare this object for serialization
	 *
	 * @return array Names of the instance variables to serialize
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __sleep() {
		return array('authenticationStatus', 'requestPatterns', 'entryPoint', 'passwordKeypairUUID', 'usernameKeypairUUID');
	}
}

?>