<?php

declare(ENCODING = 'utf-8');

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
 * @version $Id:$
 */

/**
 * An authentication token used for simple username and password authentication.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Security_Authentication_Token_UsernamePassword implements F3_FLOW3_Security_Authentication_TokenInterface {

//TODO: here we also need a user details service and an authentication entry point

	/**
	 * @var F3_FLOW3_Utility_Environment The current environment
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
	 * @var F3_FLOW3_Security_RequestPatternInterface The set request pattern
	 */
	protected $requestPattern = NULL;

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Utility_Environment $environment The current environment
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Utility_Environment $environment) {
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
	 * Returns TRUE if a F3_FLOW3_Security_RequestPattern was set
	 *
	 * @return boolean True if a F3_FLOW3_Security_RequestPatternInterface was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRequestPattern() {
		if($this->requestPattern != NULL) return TRUE;
		return FALSE;
	}

	/**
	 * Sets a F3_FLOW3_Security_RequestPattern
	 *
	 * @param F3_FLOW3_Security_RequestPatternInterface $requestPattern A request pattern
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPattern(F3_FLOW3_Security_RequestPatternInterface $requestPattern) {
		$this->requestPattern = $requestPattern;
	}

	/**
	 * Returns the set F3_FLOW3_Security_RequestPatternInterface, NULL if none was set
	 *
	 * @return F3_FLOW3_Security_RequestPatternInterface The set request pattern
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

		if(isset($POSTArguments['F3_FLOW3_Security_Authentication_Token_UsernamePassword::username'])) $this->credentials['username'] = $POSTArguments['F3_FLOW3_Security_Authentication_Token_UsernamePassword::username'];
		if(isset($POSTArguments['F3_FLOW3_Security_Authentication_Token_UsernamePassword::password'])) $this->credentials['password'] = $POSTArguments['F3_FLOW3_Security_Authentication_Token_UsernamePassword::password'];
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
	 * Might ask a F3_FLOW3_Security_Authentication_UserDetailsServiceInterface.
	 *
	 * @return F3_FLOW3_Security_Authentication_UserDetailsInterface A user details object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo implement this method
	 */
	public function getUserDetails() {

	}

	/**
	 * Returns the currently valid granted authorities. It might ask a F3_FLOW3_Security_Authentication_UserDetailsServiceInterface.
	 * Note: You have to check isAuthenticated() before you call this method
	 *
	 * @return array Array of F3_FLOW3_Security_Authentication_GrantedAuthority objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo implement this method
	 */
	public function getGrantedAuthorities() {

	}

	/**
	 * Sets the authentication status. Usually called by the responsible F3_FLOW3_Security_Authentication_ManagerInterface
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