<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authentication::Token;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @todo here we also need a user details service and an authentication entry point
 */
class UsernamePassword implements F3::FLOW3::Security::Authentication::TokenInterface {


	/**
	 * @var F3::FLOW3::Utility::Environment The current environment
	 */
	protected $environment;

	/**
	 * @var boolean Indicates wether this token is authenticated
	 */
	protected $authenticationStatus = FALSE;

	/**
	 * @var array The username/password credentials
	 */
	protected $credentials = array('username' => '', 'password' => '');

	/**
	 * @var F3::FLOW3::Security::RequestPatternInterface The set request pattern
	 */
	protected $requestPattern = NULL;

	/**
	 * Constructor
	 *
	 * @param F3::FLOW3::Utility::Environment $environment The current environment
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Utility::Environment $environment) {
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
	 * @return F3::FLOW3::Security::Authentication::EntryPoint The configured authentication entry point, NULL if none is available
	 */
	public function getAuthenticationEntryPoint() {

	}

	/**
	 * Returns TRUE if a F3::FLOW3::Security::RequestPattern was set
	 *
	 * @return boolean True if a F3::FLOW3::Security::RequestPatternInterface was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRequestPattern() {
		if ($this->requestPattern != NULL) return TRUE;
		return FALSE;
	}

	/**
	 * Sets a F3::FLOW3::Security::RequestPattern
	 *
	 * @param F3::FLOW3::Security::RequestPatternInterface $requestPattern A request pattern
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPattern(F3::FLOW3::Security::RequestPatternInterface $requestPattern) {
		$this->requestPattern = $requestPattern;
	}

	/**
	 * Returns the set F3::FLOW3::Security::RequestPatternInterface, NULL if none was set
	 *
	 * @return F3::FLOW3::Security::RequestPatternInterface The set request pattern
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

		if (isset($POSTArguments['F3::FLOW3::Security::Authentication::Token::UsernamePassword::username'])) $this->credentials['username'] = $POSTArguments['F3::FLOW3::Security::Authentication::Token::UsernamePassword::username'];
		if (isset($POSTArguments['F3::FLOW3::Security::Authentication::Token::UsernamePassword::password'])) $this->credentials['password'] = $POSTArguments['F3::FLOW3::Security::Authentication::Token::UsernamePassword::password'];
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
	 * Might ask a F3::FLOW3::Security::Authentication::UserDetailsServiceInterface.
	 *
	 * @return F3::FLOW3::Security::Authentication::UserDetailsInterface A user details object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo implement this method
	 */
	public function getUserDetails() {

	}

	/**
	 * Returns the currently valid granted authorities. It might ask a F3::FLOW3::Security::Authentication::UserDetailsServiceInterface.
	 * Note: You have to check isAuthenticated() before you call this method
	 *
	 * @return array Array of F3::FLOW3::Security::Authentication::GrantedAuthority objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo implement this method
	 */
	public function getGrantedAuthorities() {

	}

	/**
	 * Sets the authentication status. Usually called by the responsible F3::FLOW3::Security::Authentication::ManagerInterface
	 *
	 * @param boolean $authenticationStatus TRUE if the token ist authenticated, FALSE otherwise
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationStatus($authenticationStatus) {
		$this->authenticationStatus = $authenticationStatus;
	}
}

?>