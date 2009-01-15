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
 * @version $Id$
 */

/**
 * An authentication token used for simple username and password authentication.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @todo here we also need a user details service and an authentication entry point
 */
class UsernamePassword implements \F3\FLOW3\Security\Authentication\TokenInterface {

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
	protected $credentials = array('username' => '', 'password' => '');

	/**
	 * @var \F3\FLOW3\Security\RequestPatternInterface The set request pattern
	 */
	protected $requestPattern = NULL;

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
		$POSTArguments = $this->environment->getPOSTArguments();

		if (isset($POSTArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::username'])) $this->credentials['username'] = $POSTArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::username'];
		if (isset($POSTArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::password'])) $this->credentials['password'] = $POSTArguments['F3\FLOW3\Security\Authentication\Token\UsernamePassword::password'];
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
	 * Prepare this object for serialization
	 *
	 * @return array Names of the instance variables to serialize
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __sleep() {
		return (array('authenticationStatus', 'credentials', 'requestPattern'));
	}
}

?>