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
 * This is the default implementation of a security context, which holds current security information
 * like GrantedAuthorities oder details auf authenticated users.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Security_Context {

	/**
	 * @var array Array of configured tokens (might have request patterns)
	 */
	protected $tokens = array();

	/**
	 * @var boolean TRUE, if all tokens have to be authenticated, FALSE if one is sufficient.
	 */
	protected $authenticateAllTokens = FALSE;

	/**
	 * @var F3_FLOW3_MVC_Request
	 */
	protected $request;

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager The configuration manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Configuration_Manager $configurationManager) {
		$configuration = $configurationManager->getSettings('FLOW3');
		$this->authenticateAllTokens = $configuration->security->authentication->authenticateAllTokens;
	}

	/**
	 * Sets the authentication tokens in the context, usually called by the security context holder
	 *
	 * @param array $authenticationTokens Array of F3_FLOW3_Security_Authentication_TokenInterface objects
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationTokens(array $tokens) {
		$this->tokens = $tokens;
	}

	/**
	 * Sets the request the context is used for.
	 *
	 * @param F3_FLOW3_MVC_Request $request The current request
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setRequest(F3_FLOW3_MVC_Request $request) {
		$this->request = $request;
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
	 * Returns all F3_FLOW3_Security_Authentication_Tokens of the security context which are
	 * active for the current request. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @return array Array of set F3_FLOW3_Authentication_Token objects
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