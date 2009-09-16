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
 * This is the default implementation of a security context, which holds current security information
 * like GrantedAuthorities oder details auf authenticated users.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Context {

	/**
	 * Array of configured tokens (might have request patterns)
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Array of tokens currently active
	 * @var array
	 * @transient
	 */
	protected $activeTokens = array();

	/**
	 * Array of tokens currently inactive
	 * @var array
	 * @transient
	 */
	protected $inactiveTokens = array();

	/**
	 * TRUE, if all tokens have to be authenticated, FALSE if one is sufficient.
	 * @var boolean
	 */
	protected $authenticateAllTokens = FALSE;

	/**
	 * @var \F3\FLOW3\MVC\RequestInterface
	 * @transient
	 */
	protected $request;

	/**
	 * TRUE if separateActiveAndInactiveTokens has been called
	 * @var boolean
	 * @transient
	 */
	protected $separateTokensPerformed = FALSE;

	/**
	 * Injects the configuration settings
	 *
	 * @param array $settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings(array $settings) {
		$this->authenticateAllTokens = $settings['security']['authentication']['authenticateAllTokens'];
	}

	/**
	 * Sets the authentication tokens in the context, usually called by the security context holder
	 *
	 * @param array $tokens Array of \F3\FLOW3\Security\Authentication\TokenInterface objects
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationTokens(array $tokens) {
		$this->activeTokens = $tokens;
	}

	/**
	 * Sets the request the context is used for.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setRequest(\F3\FLOW3\MVC\RequestInterface $request) {
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
	 * Returns all \F3\FLOW3\Security\Authentication\Tokens of the security context which are
	 * active for the current request. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @return array Array of set \F3\FLOW3\Authentication\Token objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationTokens() {
		if ($this->separateTokensPerformed === FALSE) $this->separateActiveAndInactiveTokens();

		return $this->activeTokens;
	}

	/**
	 * Returns all \F3\FLOW3\Security\Authentication\Tokens of the security context which are
	 * active for the current request and of the given type. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @param string $className The class name
	 * @return array Array of set \F3\FLOW3\Authentication\Token objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationTokensOfType($className) {
		$activeTokens = array();

		if ($this->separateTokensPerformed === FALSE) $this->separateActiveAndInactiveTokens();

		foreach ($this->activeTokens as $token) {
			if (($token instanceof $className) === FALSE) continue;

			$activeTokens[] = $token;
		}

		return $activeTokens;
	}

	/**
	 * Returns the granted authorities of all active and authenticated tokens
	 *
	 * @return array Array of F3\FLOW3\Security\Authentication\GrantedAuthorityInterface objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getGrantedAuthorities() {
		$grantedAuthorities = array();
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated()) $grantedAuthorities = array_merge($grantedAuthorities, $token->getGrantedAuthorities());
		}

		return $grantedAuthorities;
	}

	/**
	 * Stores all active tokens in $this->activeTokens, all others in $this->inactiveTokens
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function separateActiveAndInactiveTokens() {
		$this->separateTokensPerformed = TRUE;

		foreach ($this->tokens as $token) {
			if ($token->hasRequestPatterns()) {

				$requestPatterns = $token->getRequestPatterns();
				$tokenIsActive = TRUE;

				foreach ($requestPatterns as $requestPattern) {
					if ($requestPattern->canMatch($this->request)) {
						$tokenIsActive &= $requestPattern->matchRequest($this->request);
					}
				}
				if ($tokenIsActive) {
					$this->activeTokens[] = $token;
				} else {
					$this->inactiveTokens[] = $token;
				}
			} else {
				$this->activeTokens[] = $token;
			}
		}
	}

	/**
	 * Shut the object down
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function shutdownObject() {
		$this->tokens = array_merge($this->inactiveTokens, $this->activeTokens);
	}
}

?>