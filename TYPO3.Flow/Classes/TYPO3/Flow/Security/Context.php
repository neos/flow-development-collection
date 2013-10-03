<?php
namespace TYPO3\Flow\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Utility\Algorithms;

/**
 * This is the default implementation of a security context, which holds current
 * security information like roles oder details of authenticated users.
 *
 * @Flow\Scope("session")
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
	 * Creates one csrf token per session
	 */
	const CSRF_ONE_PER_SESSION = 1;

	/**
	 * Creates one csrf token per uri
	 */
	const CSRF_ONE_PER_URI = 2;

	/**
	 * Creates one csrf token per request
	 */
	const CSRF_ONE_PER_REQUEST = 3;

	/**
	 * TRUE if the context is initialized in the current request, FALSE or NULL otherwise.
	 *
	 * @var boolean
	 * @Flow\Transient
	 */
	protected $initialized = FALSE;

	/**
	 * Array of configured tokens (might have request patterns)
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Array of tokens currently active
	 * @var array
	 * @Flow\Transient
	 */
	protected $activeTokens = array();

	/**
	 * Array of tokens currently inactive
	 * @var array
	 * @Flow\Transient
	 */
	protected $inactiveTokens = array();

	/**
	 * One of the AUTHENTICATE_* constants to set the authentication strategy.
	 * @var integer
	 */
	protected $authenticationStrategy = self::AUTHENTICATE_ANY_TOKEN;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 * @Flow\Transient
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * One of the CSRF_* constants to set the csrf protection strategy
	 * @var integer
	 */
	protected $csrfProtectionStrategy = self::CSRF_ONE_PER_SESSION;

	/**
	 * @var array
	 */
	protected $csrfProtectionTokens = array();

	/**
	 * @var \TYPO3\Flow\Mvc\RequestInterface
	 */
	protected $interceptedRequest;

	/**
	 * @Flow\Transient
	 * @var array<\TYPO3\Flow\Security\Policy\Role>
	 */
	protected $roles = NULL;

	/**
	 * Whether authorization is disabled @see areAuthorizationChecksDisabled()
	 * @Flow\Transient
	 * @var boolean
	 */
	protected $authorizationChecksDisabled = FALSE;

	/**
	 * Inject the authentication manager
	 *
	 * @param \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager The authentication manager
	 * @return void
	 */
	public function injectAuthenticationManager(Authentication\AuthenticationManagerInterface $authenticationManager) {
		$this->authenticationManager = $authenticationManager;
		$this->authenticationManager->setSecurityContext($this);
	}

	/**
	 * Lets you switch off authorization checks (CSRF token, policies, content security, ...) for the runtime of $callback
	 *
	 * Usage:
	 * $this->securityContext->withoutAuthorizationChecks(function ($accountRepository, $username, $providerName, &$account) {
	 *   // this will disable the PersistenceQueryRewritingAspect for this one call
	 *   $account = $accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($username, $providerName)
	 * });
	 *
	 * @param \Closure $callback
	 * @return void
	 * @throws \Exception
	 */
	public function withoutAuthorizationChecks(\Closure $callback) {
		$this->authorizationChecksDisabled = TRUE;
		try {
			$callback->__invoke();
		} catch (\Exception $exception) {
			$this->authorizationChecksDisabled = FALSE;
			throw $exception;
		}
		$this->authorizationChecksDisabled = FALSE;
	}

	/**
	 * Returns TRUE if authorization should be ignored, otherwise FALSE
	 * This is mainly useful to fetch records without Content Security to kick in (e.g. for AuthenticationProviders)
	 *
	 * @return boolean
	 * @see withoutAuthorizationChecks()
	 */
	public function areAuthorizationChecksDisabled() {
		return $this->authorizationChecksDisabled;
	}

	/**
	 * Set the current action request
	 *
	 * This method is called manually by the request handler which created the HTTP
	 * request.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request The current ActionRequest
	 * @return void
	 * @Flow\Autowiring(FALSE)
	 */
	public function setRequest(ActionRequest $request) {
		$this->request = $request;
	}

	/**
	 * Injects the configuration settings
	 *
	 * @param array $settings
	 * @return void
	 * @throws \TYPO3\Flow\Exception
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['security']['authentication']['authenticationStrategy'])) {
			$authenticationStrategyName = $settings['security']['authentication']['authenticationStrategy'];
			switch ($authenticationStrategyName) {
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
					throw new \TYPO3\Flow\Exception('Invalid setting "' . $authenticationStrategyName . '" for security.authentication.authenticationStrategy', 1291043022);
			}
		}

		if (isset($settings['security']['csrf']['csrfStrategy'])) {
			$csrfStrategyName = $settings['security']['csrf']['csrfStrategy'];
			switch ($csrfStrategyName) {
				case 'onePerRequest':
					$this->csrfProtectionStrategy = self::CSRF_ONE_PER_REQUEST;
					break;
				case 'onePerSession':
					$this->csrfProtectionStrategy = self::CSRF_ONE_PER_SESSION;
					break;
				case 'onePerUri':
					$this->csrfProtectionStrategy = self::CSRF_ONE_PER_URI;
					break;
				default:
					throw new \TYPO3\Flow\Exception('Invalid setting "' . $csrfStrategyName . '" for security.csrf.csrfStrategy', 1291043024);
			}
		}
	}

	/**
	 * Initializes the security context for the given request.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Exception
	 */
	public function initialize() {
		if ($this->initialized === TRUE) {
			return;
		}
		if ($this->canBeInitialized() === FALSE) {
			throw new \TYPO3\Flow\Exception('The security Context cannot be initialized yet. Please check if it can be initialized with $securityContext->canBeInitialized() before trying to do so.', 1358513802);
		}

		if ($this->csrfProtectionStrategy !== self::CSRF_ONE_PER_SESSION) {
			$this->csrfProtectionTokens = array();
		}

		$this->tokens = $this->mergeTokens($this->authenticationManager->getTokens(), $this->tokens);
		$this->separateActiveAndInactiveTokens();
		$this->updateTokens($this->activeTokens);

		$this->initialized = TRUE;
	}

	/**
	 * @return boolean TRUE if the Context is initialized, FALSE otherwise.
	 */
	public function isInitialized() {
		return $this->initialized;
	}

	/**
	 * Get the token authentication strategy
	 *
	 * @return int One of the AUTHENTICATE_* constants
	 */
	public function getAuthenticationStrategy() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		return $this->authenticationStrategy;
	}

	/**
	 * Returns all \TYPO3\Flow\Security\Authentication\Tokens of the security context which are
	 * active for the current request. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @return array<\TYPO3\Flow\Security\Authentication\TokenInterface> Array of set tokens
	 */
	public function getAuthenticationTokens() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		return $this->activeTokens;
	}

	/**
	 * Returns all \TYPO3\Flow\Security\Authentication\Tokens of the security context which are
	 * active for the current request and of the given type. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @param string $className The class name
	 * @return array<\TYPO3\Flow\Security\Authentication\TokenInterface> Array of set tokens of the specified type
	 */
	public function getAuthenticationTokensOfType($className) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		$activeTokens = array();
		foreach ($this->activeTokens as $token) {
			if ($token instanceof $className) {
				$activeTokens[] = $token;
			}
		}

		return $activeTokens;
	}

	/**
	 * Returns the roles of all authenticated accounts, including inherited roles.
	 *
	 * If no authenticated roles could be found the "Anonymous" role is returned.
	 *
	 * The "Everybody" roles is always returned.
	 *
	 * @return array<\TYPO3\Flow\Security\Policy\Role>
	 */
	public function getRoles() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if ($this->roles === NULL) {
			$this->roles = array('Everybody' => $this->policyService->getRole('Everybody'));

			if ($this->authenticationManager->isAuthenticated() === FALSE) {
				$this->roles['Anonymous'] = $this->policyService->getRole('Anonymous');
			} else {
				$this->roles['AuthenticatedUser'] = $this->policyService->getRole('AuthenticatedUser');
				/** @var $token \TYPO3\Flow\Security\Authentication\TokenInterface */
				foreach ($this->getAuthenticationTokens() as $token) {
					if ($token->isAuthenticated() === TRUE) {
						$account = $token->getAccount();
						if ($account !== NULL) {
							$accountRoles = $account->getRoles();
							/** @var $currentRole Role */
							foreach ($accountRoles as $currentRole) {
								if (!in_array($currentRole, $this->roles)) {
									$this->roles[$currentRole->getIdentifier()] = $currentRole;
								}
								/** @var $currentParentRole Role */
								foreach ($this->policyService->getAllParentRoles($currentRole) as $currentParentRole) {
									if (!in_array($currentParentRole, $this->roles)) {
										$this->roles[$currentParentRole->getIdentifier()] = $currentParentRole;
									}
								}
							}
						}
					}
				}
			}
		}

		return $this->roles;
	}

	/**
	 * Returns TRUE, if at least one of the currently authenticated accounts holds
	 * a role with the given identifier, also recursively.
	 *
	 * @param string $roleIdentifier The string representation of the role to search for
	 * @return boolean TRUE, if a role with the given string representation was found
	 */
	public function hasRole($roleIdentifier) {
		if ($roleIdentifier === 'Everybody') {
			return TRUE;
		}
		if ($roleIdentifier === 'Anonymous') {
			return (!$this->authenticationManager->isAuthenticated());
		}
		if ($roleIdentifier === 'AuthenticatedUser') {
			return ($this->authenticationManager->isAuthenticated());
		}

		$roles = $this->getRoles();
		return isset($roles[$roleIdentifier]);
	}

	/**
	 * Returns the party of the first authenticated authentication token.
	 * Note: There might be a different party authenticated in one of the later tokens,
	 * if you need it you'll have to fetch it directly from the token.
	 * (@see getAuthenticationTokens())
	 *
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The authenticated party
	 */
	public function getParty() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		/** @var $token \TYPO3\Flow\Security\Authentication\TokenInterface */
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE) {
				return $token->getAccount() !== NULL ? $token->getAccount()->getParty() : NULL;
			}
		}
		return NULL;
	}

	/**
	 * Returns the first authenticated party of the given type.
	 *
	 * @param string $className Class name of the party to find
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The authenticated party
	 */
	public function getPartyByType($className) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		/** @var $token \TYPO3\Flow\Security\Authentication\TokenInterface */
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE && $token->getAccount()->getParty() instanceof $className) {
				return $token->getAccount()->getParty();
			}
		}
		return NULL;
	}

	/**
	 * Returns the account of the first authenticated authentication token.
	 * Note: There might be a more currently authenticated account in the
	 * remaining tokens. If you need them you'll have to fetch them directly
	 * from the tokens.
	 * (@see getAuthenticationTokens())
	 *
	 * @return \TYPO3\Flow\Security\Account The authenticated account
	 */
	public function getAccount() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		/** @var $token \TYPO3\Flow\Security\Authentication\TokenInterface */
		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE) {
				return $token->getAccount();
			}
		}
		return NULL;
	}

	/**
	 * Returns an authenticated account for the given provider or NULL if no
	 * account was authenticated or no token was registered for the given
	 * authentication provider name.
	 *
	 * @param string $authenticationProviderName Authentication provider name of the account to find
	 * @return \TYPO3\Flow\Security\Account The authenticated account
	 */
	public function getAccountByAuthenticationProviderName($authenticationProviderName) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if (isset($this->activeTokens[$authenticationProviderName]) && $this->activeTokens[$authenticationProviderName]->isAuthenticated() === TRUE) {
			return $this->activeTokens[$authenticationProviderName]->getAccount();
		}
		return NULL;
	}

	/**
	 * Returns the current CSRF protection token. A new one is created when needed, depending on the  configured CSRF
	 * protection strategy.
	 *
	 * @return string
	 */
	public function getCsrfProtectionToken() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if (count($this->csrfProtectionTokens) === 1 && $this->csrfProtectionStrategy !== self::CSRF_ONE_PER_URI) {
			reset($this->csrfProtectionTokens);
			return key($this->csrfProtectionTokens);
		}
		$newToken = Algorithms::generateRandomToken(16);
		$this->csrfProtectionTokens[$newToken] = TRUE;

		return $newToken;
	}

	/**
	 * Returns TRUE if the context has CSRF protection tokens.
	 *
	 * @return boolean TRUE, if the token is valid. FALSE otherwise.
	 */
	public function hasCsrfProtectionTokens() {
		return count($this->csrfProtectionTokens) > 0;
	}

	/**
	 * Returns TRUE if the given string is a valid CSRF protection token. The token will be removed if the configured
	 * csrf strategy is 'onePerUri'.
	 *
	 * @param string $csrfToken The token string to be validated
	 * @return boolean TRUE, if the token is valid. FALSE otherwise.
	 */
	public function isCsrfProtectionTokenValid($csrfToken) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if (isset($this->csrfProtectionTokens[$csrfToken])) {
			if ($this->csrfProtectionStrategy === self::CSRF_ONE_PER_URI) {
				unset($this->csrfProtectionTokens[$csrfToken]);
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Sets an action request, to be stored for later resuming after it
	 * has been intercepted by a security exception.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $interceptedRequest
	 * @return void
	 * @Flow\Session(autoStart=true)
	 */
	public function setInterceptedRequest(ActionRequest $interceptedRequest = NULL) {
		$this->interceptedRequest = $interceptedRequest;
	}

	/**
	 * Returns the request, that has been stored for later resuming after it
	 * has been intercepted by a security exception, NULL if there is none.
	 *
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function getInterceptedRequest() {
		return $this->interceptedRequest;
	}

	/**
	 * Clears the security context.
	 *
	 * @return void
	 */
	public function clearContext() {
		$this->roles = NULL;
		$this->tokens = array();
		$this->activeTokens = array();
		$this->inactiveTokens = array();
		$this->request = NULL;
		$this->csrfProtectionTokens = array();
		$this->interceptedRequest = NULL;
		$this->initialized = FALSE;
	}

	/**
	 * Stores all active tokens in $this->activeTokens, all others in $this->inactiveTokens
	 *
	 * @return void
	 */
	protected function separateActiveAndInactiveTokens() {
		if ($this->request === NULL) {
			return;
		}

		/** @var $token \TYPO3\Flow\Security\Authentication\TokenInterface */
		foreach ($this->tokens as $token) {
			if ($token->hasRequestPatterns()) {

				$requestPatterns = $token->getRequestPatterns();
				$tokenIsActive = TRUE;

				/** @var $requestPattern \TYPO3\Flow\Security\RequestPatternInterface */
				foreach ($requestPatterns as $requestPattern) {
					$tokenIsActive &= $requestPattern->matchRequest($this->request);
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
	 * @param array $sessionTokens Array of tokens restored from the session
	 * @return array Array of \TYPO3\Flow\Security\Authentication\TokenInterface objects
	 */
	protected function mergeTokens($managerTokens, $sessionTokens) {
		$resultTokens = array();

		if (!is_array($managerTokens)) {
			return $resultTokens;
		}

		/** @var $managerToken \TYPO3\Flow\Security\Authentication\TokenInterface */
		foreach ($managerTokens as $managerToken) {
			$noCorrespondingSessionTokenFound = TRUE;

			if (!is_array($sessionTokens)) {
				continue;
			}

			/** @var $sessionToken \TYPO3\Flow\Security\Authentication\TokenInterface */
			foreach ($sessionTokens as $sessionToken) {
				if ($sessionToken->getAuthenticationProviderName() === $managerToken->getAuthenticationProviderName()) {
					$resultTokens[$sessionToken->getAuthenticationProviderName()] = $sessionToken;
					$noCorrespondingSessionTokenFound = FALSE;
				}
			}

			if ($noCorrespondingSessionTokenFound) {
				$resultTokens[$managerToken->getAuthenticationProviderName()] = $managerToken;
			}
		}

		return $resultTokens;
	}

	/**
	 * Updates the token credentials for all tokens in the given array.
	 *
	 * @param array $tokens Array of authentication tokens the credentials should be updated for
	 * @return void
	 */
	protected function updateTokens(array $tokens) {
		if ($this->request !== NULL) {
			/** @var $token \TYPO3\Flow\Security\Authentication\TokenInterface */
			foreach ($tokens as $token) {
				$token->updateCredentials($this->request);
			}
		}

		$this->roles = NULL;
	}

	/**
	 * Refreshes all active tokens by updating the credentials.
	 * This is useful when doing an explicit authentication inside a request.
	 *
	 * @return void
	 */
	public function refreshTokens() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		$this->updateTokens($this->activeTokens);
	}

	/**
	 * Shut the object down
	 *
	 * @return void
	 */
	public function shutdownObject() {
		$this->tokens = array_merge($this->inactiveTokens, $this->activeTokens);
		$this->initialized = FALSE;
	}

	/**
	 * Check if the securityContext is ready to be initialized. Only after that security will be active.
	 *
	 * To be able to initialize, there needs to be an ActionRequest available, usually that is
	 * provided by the MVC router.
	 *
	 * @return boolean
	 */
	public function canBeInitialized() {
		if ($this->request === NULL) {
			return FALSE;
		}
		return TRUE;
	}
}

?>