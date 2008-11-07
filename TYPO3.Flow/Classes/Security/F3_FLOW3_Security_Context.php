<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security;

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
 * This is the default implementation of a security context, which holds current security information
 * like GrantedAuthorities oder details auf authenticated users.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Context {

	/**
	 * Array of configured tokens (might have request patterns)
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * TRUE, if all tokens have to be authenticated, FALSE if one is sufficient.
	 * @var boolean
	 */
	protected $authenticateAllTokens = FALSE;

	/**
	 * @var F3::FLOW3::MVC::Request
	 */
	protected $request;

	/**
	 * TRUE if the tokens already have been authenticated in this request
	 * @var boolean
	 */
	protected $authenticationPerformed = FALSE;

	/**
	 * Constructor.
	 *
	 * @param F3::FLOW3::Configuration::Manager $configurationManager The configuration manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Configuration::Manager $configurationManager) {
		$settings = $configurationManager->getSettings('FLOW3');
		$this->authenticateAllTokens = $settings['security']['authentication']['authenticateAllTokens'];
	}

	/**
	 * Sets the authentication tokens in the context, usually called by the security context holder
	 *
	 * @param array $authenticationTokens Array of F3::FLOW3::Security::Authentication::TokenInterface objects
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationTokens(array $tokens) {
		$this->tokens = $tokens;
	}

	/**
	 * Sets the request the context is used for.
	 *
	 * @param F3::FLOW3::MVC::Request $request The current request
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setRequest(F3::FLOW3::MVC::Request $request) {
		$this->request = $request;
	}

	/**
	 * Sets the authentication performed flag. If it is set to TRUE
	 * the authentication manager will not reauthenticate the tokens
	 * in the current request.
	 *
	 * @param boolean $status Set this to TRUE, if the tokens have been authenticated in this request
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationPerformed($status) {
		$this->authenticationPerformed = $status;
	}

	/**
	 * Returns TRUE if the tokens already have been authenticated in this request
	 *
	 * @return boolean TRUE if the tokens already have been authenticated in this request
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticationPerformed() {
		return $this->authenticationPerformed;
	}

	/**
	 * Returns TRUE, if all active tokens have to be authenticated.
	 *
	 * @return boolean TRUE, if all active tokens have to be authenticated.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAllTokens() {
		return $this->authenticateAllTokens;
	}

	/**
	 * Returns all F3::FLOW3::Security::Authentication::Tokens of the security context which are
	 * active for the current request. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @return array Array of set F3::FLOW3::Authentication::Token objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo cache tokens active for the current request
	 */
	public function getAuthenticationTokens() {
		$activeTokens = array();

		foreach ($this->tokens as $token) {
			if ($token->hasRequestPattern()) {

				$requestPattern = $token->getRequestPattern();
				if ($requestPattern->canMatch($this->request) && $requestPattern->matchRequest($this->request)) {
					$activeTokens[] = $token;
				}
			} else {
				$activeTokens[] = $token;
			}
		}

		return $activeTokens;
	}

	/**
	 * Returns the granted authorities of all active and authenticated tokens
	 *
	 * @return array Array of F3_FLOW3_Security_Authentication_GrantedAuthorityInterface objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getGrantedAuthorities() {

	}

	/**
	 * Prepare this object for serialization
	 *
	 * @return array Names of the instance variables to serialize
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __sleep() {
		return (array('tokens', 'authenticateAllTokens'));
	}
}

?>