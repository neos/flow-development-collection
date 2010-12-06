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
 * This is the default implementation of a security context, which holds current
 * security information like Roles oder details auf authenticated users.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope session
 */
class Context {

	/**
	 * Authenticate as many tokens as possible but do not require
	 * an authenticated token (e.g. for guest users with role Everybody).
	 */
	const AUTHENTICATE_ANY_TOKEN = 1;

	/**
	 * Stop authentication of tokens after first successful
	 * authentication of a token.
	 */
	const AUTHENTICATE_ONE_TOKEN = 2;

	/**
	 * Authenticate all active tokens and throw an exception if
	 * an active token could not be authenticated.
	 */
	const AUTHENTICATE_ALL_TOKENS = 3;

	/**
	 * Authenticate as many tokens as possible but do not fail if
	 * a token could not be authenticated and at least one token
	 * could be authenticated.
	 */
	const AUTHENTICATE_AT_LEAST_ONE_TOKEN = 4;

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
	 * One of the AUTHENTICATE_* constants to set the authentication strategy.
	 * @var int
	 */
	protected $authenticationStrategy = self::AUTHENTICATE_ANY_TOKEN;

	/**
	 * @var \F3\FLOW3\MVC\RequestInterface
	 * @transient
	 */
	protected $request;

	/**
	 * @var F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var F3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var \F3\FLOW3\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * Inject the object manager
	 *
	 * @param F3\FLOW3\Object\ObjectManagerInterface $objectManager The object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Inject the authentication manager
	 *
	 * @param F3\FLOW3\Security\Authentication\AuthenticationManagerInterface $authenticationManager The authentication manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAuthenticationManager(\F3\FLOW3\Security\Authentication\AuthenticationManagerInterface $authenticationManager) {
		$this->authenticationManager = $authenticationManager;
		$this->authenticationManager->setSecurityContext($this);
	}

	/**
	 * Injects the security context
	 *
	 * @param \F3\FLOW3\Security\Policy\PolicyService $policyService The policy service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectPolicyService(\F3\FLOW3\Security\Policy\PolicyService $policyService) {
		$this->policyService = $policyService;
	}

	/**
	 * Injects the configuration settings
	 *
	 * @param array $settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['security']['authentication']['authenticationStrategy'])) {
			$authenticationStrategyName = $settings['security']['authentication']['authenticationStrategy'];
			switch($authenticationStrategyName) {
				case 'allTokens':
					$this->authenticationStrategy = self::AUTHENTICATE_ALL_TOKENS;
					break;
				case 'oneToken':
					$this->authenticationStrategy = self::AUTHENTICATE_ONE_TOKEN;
					break;
				case 'atLeastOneToken':
					$this->authenticationStrategy = self::AUTHENTICATE_AT_LEAST_ONE_TOKEN;
					break;
				case 'anyToken':
					$this->authenticationStrategy = self::AUTHENTICATE_ANY_TOKEN;
					break;
				default:
					throw new \F3\FLOW3\Exception('Invalid setting "' . $authenticationStrategyName . '" for security.authentication.authenticationStrategy', 1291043022);
			}
		}
	}

	/**
	 * Initializes the security context for the given request.
	 *
	 * @param F3\FLOW3\MVC\RequestInterface $request The request the context should be initialized for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(\F3\FLOW3\MVC\RequestInterface $request) {
		$this->request = $request;

		$mergedTokens = $this->mergeTokens($this->filterInactiveTokens($this->authenticationManager->getTokens(), $request), $this->tokens);

		$this->updateTokens($mergedTokens);
		$this->tokens = $mergedTokens;
		$this->separateActiveAndInactiveTokens();
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
		foreach ($this->activeTokens as $token) {
			if (($token instanceof $className) === FALSE) continue;

			$activeTokens[] = $token;
		}

		return $activeTokens;
	}

	/**
	 * Returns the roles of all active and authenticated tokens.
	 * If no authenticated roles could be found the "Everbody" role is returned
	 *
	 * @return array Array of F3\FLOW3\Security\Policy\Role objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRoles() {
		$roles = array($this->objectManager->get('F3\FLOW3\Security\Policy\Role', 'Everybody'));
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated()) {
				$tokenRoles = $token->getRoles();
				foreach ($tokenRoles as $currentRole) {
					if (!in_array($currentRole, $roles)) $roles[] = $currentRole;
					foreach ($this->policyService->getAllParentRoles($currentRole) as $currentParentRole) {
						if (!in_array($currentParentRole, $roles)) $roles[] = $currentParentRole;
					}
				}
			}
		}

		return $roles;
	}

	/**
	 * Returns TRUE, if at least one of the currently authenticated tokens holds
	 * a role with the given string representation
	 *
	 * @param string $role The string representation of the role to search for
	 * @return boolean TRUE, if a role with the given string representation was found
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRole($role) {
		$authenticatedRolesExist = FALSE;
		foreach ($this->getAuthenticationTokens() as $token) {
			$tokenRoles = $token->getRoles();
			if ($token->isAuthenticated() && in_array($role, $tokenRoles)) {
				return TRUE;
			} else {
				if (count($tokenRoles) > 0) $authenticatedRolesExist = TRUE;
			}
		}

		if (!$authenticatedRolesExist && ((string)$role === 'Everybody')) return TRUE;

		return FALSE;
	}

	/**
	 * Returns the party of the first authenticated authentication token.
	 * Note: There might be a different party authenticated in one of the later tokens,
	 * if you need it you'll have to fetch it directly from the token.
	 * (@see getAuthenticationTokens())
	 *
	 * @return \F3\Party\Domain\Model\Party The authenticated party
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getParty() {
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE) return $token->getAccount()->getParty();
		}
		return NULL;
	}

	/**
	 * Returns the first authenticated party of the given type.
	 *
	 * @param string $className Class name of the party to find
	 * @return \F3\Party\Domain\Model\Party The authenticated party
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPartyByType($className) {
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE && $token->getAccount()->getParty() instanceof $className) {
				return $token->getAccount()->getParty();
			}
		}
		return NULL;
	}

	/**
	 * Returns the account of the first authenticated authentication token.
	 * Note: There might be a more currently authenticated accounts in the
	 * remaining tokens. If you need them you'll have to fetch them directly
	 * from the tokens.
	 * (@see getAuthenticationTokens())
	 *
	 * @return \F3\FLOW3\Security\Account The authenticated account
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAccount() {
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE) return $token->getAccount();
		}
		return NULL;
	}

	/**
	 * Returns an authenticated account for the given provider or NULL if no
	 * account was authenticated or no token was registered for the given
	 * authentication provider name.
	 *
	 * @param string $authenticationProviderName Authentication provider name of the account to find
	 * @return \F3\FLOW3\Security\Account The authenticated account
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getAccountByAuthenticationProviderName($authenticationProviderName) {
		if (isset($this->activeTokens[$authenticationProviderName]) && $this->activeTokens[$authenticationProviderName]->isAuthenticated() === TRUE) {
			return $this->activeTokens[$authenticationProviderName]->getAccount();
		}
		return NULL;
	}

	/**
	 * Clears the security context.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function clearContext() {
		$this->tokens = NULL;
		$this->activeTokens = NULL;
		$this->inactiveTokens = NULL;
		$this->request = NULL;
		$this->authenticationStrategy = self::AUTHENTICATE_ONE_TOKEN;
	}

	/**
	 * Stores all active tokens in $this->activeTokens, all others in $this->inactiveTokens
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function separateActiveAndInactiveTokens() {
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
					$this->activeTokens[$token->getAuthenticationProviderName()] = $token;
				} else {
					$this->inactiveTokens[$token->getAuthenticationProviderName()] = $token;
				}
			} else {
				$this->activeTokens[$token->getAuthenticationProviderName()] = $token;
			}
		}
	}

	/**
	 * Merges the session and manager tokens. All manager tokens types will be in the result array
	 * If a specific type is found in the session this token replaces the one (of the same type)
	 * given by the manager.
	 *
	 * @param array $managerTokens Array of tokens provided by the authentication manager
	 * @param array $sessionTokens Array of tokens resotored from the session
	 * @return array Array of \F3\FLOW3\Security\Authentication\TokenInterface objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function mergeTokens($managerTokens, $sessionTokens) {
		$resultTokens = array();

		if (!is_array($managerTokens)) return $resultTokens;

		foreach ($managerTokens as $managerToken) {
			$noCorrespondingSessionTokenFound = TRUE;

			if (!is_array($sessionTokens)) continue;

			foreach ($sessionTokens as $sessionToken) {
				if (get_class($sessionToken) === get_class($managerToken)) {
					$resultTokens[$sessionToken->getAuthenticationProviderName()] = $sessionToken;
					$noCorrespondingSessionTokenFound = FALSE;
				}
			}

			if ($noCorrespondingSessionTokenFound) $resultTokens[$managerToken->getAuthenticationProviderName()] = $managerToken;
		}

		return $resultTokens;
	}

	/**
	 * Filters all tokens that don't match for the given request.
	 *
	 * @param array $tokens The token array to be filtered
	 * @param F3\FLOW3\MVC\RequestInterface $request The request object
	 * @return array The filtered token array
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function filterInactiveTokens(array $tokens, \F3\FLOW3\MVC\RequestInterface $request) {
		$activeTokens = array();

		foreach ($tokens as $token) {
			if ($token->hasRequestPatterns()) {
				$requestPatterns = $token->getRequestPatterns();
				$tokenIsActive = TRUE;

				foreach ($requestPatterns as $requestPattern) {
					if ($requestPattern->canMatch($request)) {
						$tokenIsActive &= $requestPattern->matchRequest($request);
					}
				}
				if ($tokenIsActive) $activeTokens[$token->getAuthenticationProviderName()] = $token;

			} else {
				$activeTokens[$token->getAuthenticationProviderName()] = $token;
			}
		}

		return $activeTokens;
	}

	/**
	 * Updates the token credentials for all tokens in the given array.
	 *
	 * @param array $tokens Array of authentication tokens the credentials should be updated for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function updateTokens(array $tokens) {
		foreach ($tokens as $token) {
			$token->updateCredentials($this->request);
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

	/**
	 * Get the token authentication strategy
	 *
	 * @return int One of the AUTHENTICATE_* constants
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getAuthenticationStrategy() {
		return $this->authenticationStrategy;
	}
}

?>