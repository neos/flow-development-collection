<?php
namespace Neos\Flow\Security;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Session\SessionManagerInterface;
use Neos\Flow\Utility\Algorithms;
use Neos\Utility\TypeHandling;
use Psr\Log\LoggerInterface;

/**
 * This is the default implementation of a security context, which holds current
 * security information like roles oder details of authenticated users.
 *
 * @Flow\Scope("singleton")
 */
class Context
{
    /**
     * Authenticate as many tokens as possible but do not require
     * an authenticated token (e.g. for guest users with role Neos.Flow:Everybody).
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
     * If the security context isn't initialized (or authorization checks are disabled)
     * this constant will be returned by getContextHash()
     */
    const CONTEXT_HASH_UNINITIALIZED = '__uninitialized__';

    /**
     * One of the AUTHENTICATE_* constants to set the authentication strategy.
     *
     * @var integer
     */
    protected $authenticationStrategy = self::AUTHENTICATE_ANY_TOKEN;

    /**
     * One of the CSRF_* constants to set the csrf protection strategy
     *
     * @var integer
     */
    protected $csrfProtectionStrategy = self::CSRF_ONE_PER_SESSION;

    /**
     * @var array
     */
    protected $tokenStatusLabels = [
        1 => 'no credentials given',
        2 => 'wrong credentials',
        3 => 'authentication successful',
        4 => 'authentication needed'
    ];

    /**
     * true if the context is initialized in the current request, false or NULL otherwise.
     *
     * @var boolean
     */
    protected $initialized = false;

    /**
     * Array of tokens currently active
     * @var TokenInterface[]
     */
    protected $activeTokens = [];

    /**
     * Array of tokens currently inactive
     * @var TokenInterface[]
     */
    protected $inactiveTokens = [];

    /**
     * @var ActionRequest
     */
    protected $request;

    /**
     * @var Role[]
     */
    protected $roles = null;

    /**
     * Whether authorization is disabled @see areAuthorizationChecksDisabled()
     *
     * @var boolean
     */
    protected $authorizationChecksDisabled = false;

    /**
     * A hash for this security context that is unique to the currently authenticated roles. @see getContextHash()
     *
     * @var string
     */
    protected $contextHash = null;

    /**
     * CSRF tokens that are valid during this request but will be gone after.
     * @var array
     */
    protected $csrfTokensRemovedAfterCurrentRequest = [];

    /**
     * CSRF token created in the current request.
     * @var string
     */
    protected $requestCsrfToken = '';

    /**
     * @Flow\Inject
     * @var TokenAndProviderFactoryInterface
     */
    protected $tokenAndProviderFactory;

    /**
     * @Flow\Inject
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @Flow\Inject(name="Neos.Flow:SecurityLogger")
     * @var LoggerInterface
     */
    protected $securityLogger;

    /**
     * @Flow\Inject
     * @var Policy\PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Array of registered global objects that can be accessed as operands
     *
     * @Flow\InjectConfiguration("aop.globalObjects")
     * @var array
     */
    protected $globalObjects = [];


    /**
     * Lets you switch off authorization checks (CSRF token, policies, content security, ...) for the runtime of $callback
     *
     * Usage:
     * $this->securityContext->withoutAuthorizationChecks(function () use ($accountRepository, $username, $providerName, &$account) {
     *   // this will disable the PersistenceQueryRewritingAspect for this one call
     *   $account = $accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($username, $providerName)
     * });
     *
     * @param \Closure $callback
     * @return void
     * @throws \Exception
     */
    public function withoutAuthorizationChecks(\Closure $callback)
    {
        $authorizationChecksAreAlreadyDisabled = $this->authorizationChecksDisabled;
        $this->authorizationChecksDisabled = true;
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $callback->__invoke();
        } catch (\Exception $exception) {
            $this->authorizationChecksDisabled = false;
            throw $exception;
        }
        if ($authorizationChecksAreAlreadyDisabled === false) {
            $this->authorizationChecksDisabled = false;
        }
    }

    /**
     * Returns true if authorization should be ignored, otherwise false
     * This is mainly useful to fetch records without Content Security to kick in (e.g. for AuthenticationProviders)
     *
     * @return boolean
     * @see withoutAuthorizationChecks()
     */
    public function areAuthorizationChecksDisabled()
    {
        return $this->authorizationChecksDisabled;
    }

    /**
     * Set the current action request
     *
     * This method is called manually by the request handler which created the HTTP
     * request.
     *
     * @param ActionRequest $request The current ActionRequest
     * @return void
     * @Flow\Autowiring(false)
     */
    public function setRequest(ActionRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Injects the configuration settings
     *
     * @param array $settings
     * @return void
     * @throws Exception
     */
    public function injectSettings(array $settings)
    {
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
                    throw new Exception('Invalid setting "' . $authenticationStrategyName . '" for security.authentication.authenticationStrategy', 1291043022);
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
                    throw new Exception('Invalid setting "' . $csrfStrategyName . '" for security.csrf.csrfStrategy', 1291043024);
            }
        }
    }

    /**
     * Initializes the security context for the given request.
     *
     * @return void
     * @throws Exception
     */
    public function initialize()
    {
        if ($this->initialized === true) {
            return;
        }
        if ($this->canBeInitialized() === false) {
            throw new Exception('The security Context cannot be initialized yet. Please check if it can be initialized with $securityContext->canBeInitialized() before trying to do so.', 1358513802);
        }

        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        $factoryTokens = $this->tokenAndProviderFactory->getTokens();
        $tokens = $this->mergeTokens($factoryTokens, $sessionDataContainer->getSecurityTokens());
        $this->separateActiveAndInactiveTokens($tokens);
        $this->updateTokens($this->activeTokens);

        $this->initialized = true;
    }

    /**
     * @return boolean true if the Context is initialized, false otherwise.
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Get the token authentication strategy
     *
     * @return int One of the AUTHENTICATE_* constants
     */
    public function getAuthenticationStrategy()
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        return $this->authenticationStrategy;
    }

    /**
     * Returns all Authentication\Tokens of the security context which are
     * active for the current request. If a token has a request pattern that cannot match
     * against the current request it is determined as not active.
     *
     * @return TokenInterface[] Array of set tokens
     */
    public function getAuthenticationTokens()
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        return $this->activeTokens;
    }

    /**
     * Returns all Authentication\Tokens of the security context which are
     * active for the current request and of the given type. If a token has a request pattern that cannot match
     * against the current request it is determined as not active.
     *
     * @param string $className The class name
     * @return TokenInterface[] Array of set tokens of the specified type
     */
    public function getAuthenticationTokensOfType($className)
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        $activeTokens = [];
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
     * The "Neos.Flow:Everybody" roles is always returned.
     *
     * @return Role[]
     * @throws Exception
     * @throws Exception\NoSuchRoleException
     * @throws InvalidConfigurationTypeException
     */
    public function getRoles()
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        if ($this->roles !== null) {
            return $this->roles;
        }

        $this->roles = ['Neos.Flow:Everybody' => $this->policyService->getRole('Neos.Flow:Everybody')];

        $authenticatedTokens = array_filter($this->getAuthenticationTokens(), static function (TokenInterface $token) {
            return $token->isAuthenticated();
        });

        if (empty($authenticatedTokens)) {
            $this->roles['Neos.Flow:Anonymous'] = $this->policyService->getRole('Neos.Flow:Anonymous');
            return $this->roles;
        }

        $this->roles['Neos.Flow:AuthenticatedUser'] = $this->policyService->getRole('Neos.Flow:AuthenticatedUser');

        /** @var $token TokenInterface */
        foreach ($authenticatedTokens as $token) {
            $account = $token->getAccount();
            if ($account === null) {
                continue;
            }

            $this->roles = array_merge($this->roles, $this->collectRolesAndParentRolesFromAccount($account));
        }

        return $this->roles;
    }

    /**
     * Returns true, if at least one of the currently authenticated accounts holds
     * a role with the given identifier, also recursively.
     *
     * @param string $roleIdentifier The string representation of the role to search for
     * @return boolean true, if a role with the given string representation was found
     * @throws Exception
     * @throws Exception\NoSuchRoleException
     */
    public function hasRole($roleIdentifier)
    {
        if ($roleIdentifier === 'Neos.Flow:Everybody') {
            return true;
        }

        $roles = $this->getRoles();
        return isset($roles[$roleIdentifier]);
    }

    /**

     * Returns the account of the first authenticated authentication token.
     * Note: There might be a more currently authenticated account in the
     * remaining tokens. If you need them you'll have to fetch them directly
     * from the tokens.
     * (@see getAuthenticationTokens())
     *
     * @return Account The authenticated account
     */
    public function getAccount()
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        /** @var $token TokenInterface */
        foreach ($this->getAuthenticationTokens() as $token) {
            if ($token->isAuthenticated() === true) {
                return $token->getAccount();
            }
        }
        return null;
    }

    /**
     * Returns an authenticated account for the given provider or NULL if no
     * account was authenticated or no token was registered for the given
     * authentication provider name.
     *
     * @param string $authenticationProviderName Authentication provider name of the account to find
     * @return Account The authenticated account
     */
    public function getAccountByAuthenticationProviderName($authenticationProviderName)
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        if (isset($this->activeTokens[$authenticationProviderName]) && $this->activeTokens[$authenticationProviderName]->isAuthenticated() === true) {
            return $this->activeTokens[$authenticationProviderName]->getAccount();
        }
        return null;
    }

    /**
     * Returns the current CSRF protection token. A new one is created when needed, depending on the  configured CSRF
     * protection strategy.
     *
     * @return string
     * @Flow\Session(autoStart=true)
     */
    public function getCsrfProtectionToken()
    {
        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        $csrfProtectionTokens = $sessionDataContainer->getCsrfProtectionTokens();
        if ($this->csrfProtectionStrategy === self::CSRF_ONE_PER_SESSION && count($csrfProtectionTokens) === 1) {
            reset($csrfProtectionTokens);
            return key($csrfProtectionTokens);
        }

        if ($this->csrfProtectionStrategy === self::CSRF_ONE_PER_REQUEST) {
            if (empty($this->requestCsrfToken)) {
                $this->requestCsrfToken = Algorithms::generateRandomToken(16);
                $csrfProtectionTokens[$this->requestCsrfToken] = true;
                $sessionDataContainer->setCsrfProtectionTokens($csrfProtectionTokens);
            }

            return $this->requestCsrfToken;
        }

        $newToken = Algorithms::generateRandomToken(16);
        $csrfProtectionTokens[$newToken] = true;
        $sessionDataContainer->setCsrfProtectionTokens($csrfProtectionTokens);

        return $newToken;
    }

    /**
     * Returns true if the context has CSRF protection tokens.
     *
     * @return boolean true, if the token is valid. false otherwise.
     */
    public function hasCsrfProtectionTokens()
    {
        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        $csrfProtectionTokens = $sessionDataContainer->getCsrfProtectionTokens();
        return count($csrfProtectionTokens) > 0;
    }

    /**
     * Returns true if the given string is a valid CSRF protection token. The token will be removed if the configured
     * csrf strategy is 'onePerUri'.
     *
     * @param string $csrfToken The token string to be validated
     * @return boolean true, if the token is valid. false otherwise.
     * @throws Exception
     */
    public function isCsrfProtectionTokenValid($csrfToken)
    {
        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        $csrfProtectionTokens = $sessionDataContainer->getCsrfProtectionTokens();

        if (!isset($csrfProtectionTokens[$csrfToken]) && !isset($this->csrfTokensRemovedAfterCurrentRequest[$csrfToken])) {
            return false;
        }

        if ($this->csrfProtectionStrategy === self::CSRF_ONE_PER_URI) {
            unset($csrfProtectionTokens[$csrfToken]);
        }

        if ($this->csrfProtectionStrategy === self::CSRF_ONE_PER_REQUEST) {
            $this->csrfTokensRemovedAfterCurrentRequest[$csrfToken] = true;
            unset($csrfProtectionTokens[$csrfToken]);
        }

        $sessionDataContainer->setCsrfProtectionTokens($csrfProtectionTokens);
        return true;
    }

    /**
     * Sets an action request, to be stored for later resuming after it
     * has been intercepted by a security exception.
     *
     * @param ActionRequest $interceptedRequest
     * @return void
     * @Flow\Session(autoStart=true)
     */
    public function setInterceptedRequest(ActionRequest $interceptedRequest = null)
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        $sessionDataContainer->setInterceptedRequest($interceptedRequest);
    }

    /**
     * Returns the request, that has been stored for later resuming after it
     * has been intercepted by a security exception, NULL if there is none.
     *
     * @return ActionRequest|null
     * TODO: Revisit type (ActionRequest / HTTP request)
     */
    public function getInterceptedRequest()
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        return $sessionDataContainer->getInterceptedRequest();
    }

    /**
     * Clears the security context.
     *
     * @return void
     */
    public function clearContext()
    {
        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        $sessionDataContainer->reset();

        $this->roles = null;
        $this->contextHash = null;
        $this->activeTokens = [];
        $this->inactiveTokens = [];
        $this->request = null;
        $this->authorizationChecksDisabled = false;
        $this->initialized = false;
    }

    /**
     * @param Account $account
     * @return array
     */
    protected function collectRolesAndParentRolesFromAccount(Account $account): array
    {
        $reducer = function (array $roles, $currentRole) {
            $roles[$currentRole->getIdentifier()] = $currentRole;
            $roles = array_merge($roles, $this->collectParentRoles($currentRole));

            return $roles;
        };

        return array_reduce($account->getRoles(), $reducer, []);
    }

    /**
     * @param Role $role
     * @return array
     */
    protected function collectParentRoles(Role $role): array
    {
        $reducer = function (array $roles, Role $parentRole) {
            $roles[$parentRole->getIdentifier()] = $parentRole;
            return $roles;
        };

        return array_reduce($role->getAllParentRoles(), $reducer, []);
    }

    /**
     * Stores all active tokens in $this->activeTokens, all others in $this->inactiveTokens
     *
     * @param TokenInterface[] $tokens
     * @return void
     */
    protected function separateActiveAndInactiveTokens(array $tokens)
    {
        if ($this->request === null) {
            return;
        }

        /** @var $token TokenInterface */
        foreach ($tokens as $token) {
            if ($this->isTokenActive($token)) {
                $this->activeTokens[$token->getAuthenticationProviderName()] = $token;
            } else {
                $this->inactiveTokens[$token->getAuthenticationProviderName()] = $token;
            }
        }
    }

    /**
     * Evaluates any RequestPatterns of the given token to determine whether it is active for the current request
     * - If no RequestPattern is configured for this token, it is active
     * - Otherwise it is active only if at least one configured RequestPattern per type matches the request
     *
     * @param TokenInterface $token
     * @return bool true if the given token is active, otherwise false
     */
    protected function isTokenActive(TokenInterface $token)
    {
        if (!$token->hasRequestPatterns()) {
            return true;
        }
        $requestPatternsByType = [];
        /** @var $requestPattern RequestPatternInterface */
        foreach ($token->getRequestPatterns() as $requestPattern) {
            $patternType = TypeHandling::getTypeForValue($requestPattern);
            if (isset($requestPatternsByType[$patternType]) && $requestPatternsByType[$patternType] === true) {
                continue;
            }
            $requestPatternsByType[$patternType] = $requestPattern->matchRequest($this->request);
        }
        return !in_array(false, $requestPatternsByType, true);
    }

    /**
     * Merges the session and manager tokens. All manager tokens types will be in the result array
     * If a specific type is found in the session this token replaces the one (of the same type)
     * given by the manager.
     *
     * @param array $managerTokens Array of tokens provided by the authentication manager
     * @param array $sessionTokens Array of tokens restored from the session
     * @return array Array of Authentication\TokenInterface objects
     */
    protected function mergeTokens($managerTokens, $sessionTokens)
    {
        $resultTokens = [];

        if (!is_array($managerTokens)) {
            return $resultTokens;
        }

        $sessionTokens = $sessionTokens ?? [];

        /** @var $managerToken TokenInterface */
        foreach ($managerTokens as $managerToken) {
            $resultTokens[$managerToken->getAuthenticationProviderName()] = $this->findBestMatchingToken($managerToken, $sessionTokens);
        }

        return $resultTokens;
    }

    /**
     * Tries to find a token matchting the given manager token in the session tokens, will return that or the manager token.
     *
     * @param TokenInterface $managerToken
     * @param TokenInterface[] $sessionTokens
     * @return TokenInterface
     * @throws \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    protected function findBestMatchingToken(TokenInterface $managerToken, array $sessionTokens): TokenInterface
    {
        $matchingSessionTokens = array_filter($sessionTokens, function (TokenInterface $sessionToken) use ($managerToken) {
            return ($sessionToken->getAuthenticationProviderName() === $managerToken->getAuthenticationProviderName());
        });

        if (empty($matchingSessionTokens)) {
            return $managerToken;
        }

        $matchingSessionToken = reset($matchingSessionTokens);

        $session = $this->sessionManager->getCurrentSession();
        $this->securityLogger->debug(
            sprintf(
                'Session %s contains auth token %s for provider %s. Status: %s',
                $session->getId(),
                get_class($matchingSessionToken),
                $matchingSessionToken->getAuthenticationProviderName(),
                $this->tokenStatusLabels[$matchingSessionToken->getAuthenticationStatus()]
            ),
            LogEnvironment::fromMethodName(__METHOD__)
        );

        return $matchingSessionToken;
    }

    /**
     * Updates the token credentials for all tokens in the given array.
     *
     * @param array $tokens Array of authentication tokens the credentials should be updated for
     * @return void
     */
    protected function updateTokens(array $tokens)
    {
        $this->roles = null;
        $this->contextHash = null;

        if ($this->request === null) {
            return;
        }

        /** @var $token TokenInterface */
        foreach ($tokens as $token) {
            $token->updateCredentials($this->request);
        }

        $tokensForSession = array_filter(array_merge($this->inactiveTokens, $this->activeTokens), static function (TokenInterface $token) {
            return !$token instanceof SessionlessTokenInterface;
        });
        $sessionDataContainer = $this->objectManager->get(SessionDataContainer::class);
        $sessionDataContainer->setSecurityTokens($tokensForSession);
    }

    /**
     * Refreshes all active tokens by updating the credentials.
     * This is useful when doing an explicit authentication inside a request.
     *
     * @return void
     */
    public function refreshTokens()
    {
        if ($this->initialized === false) {
            $this->initialize();
        }

        $this->updateTokens($this->activeTokens);
    }

    /**
     * Refreshes the currently effective roles. In fact the roles first level cache
     * is reset and the effective roles get recalculated by calling getRoles().
     *
     * @return void
     */
    public function refreshRoles()
    {
        $this->roles = null;
        $this->contextHash = null;
        $this->getRoles();
    }

    /**
     * Check if the securityContext is ready to be initialized. Only after that security will be active.
     *
     * To be able to initialize, there needs to be an ActionRequest available, usually that is
     * provided by the MVC router.
     *
     * @return boolean
     */
    public function canBeInitialized()
    {
        if ($this->request === null) {
            return false;
        }
        return true;
    }

    /**
     * Returns a hash that is unique for the current context, depending on hash components, @see setContextHashComponent()
     *
     * @return string
     */
    public function getContextHash()
    {
        if ($this->areAuthorizationChecksDisabled()) {
            return self::CONTEXT_HASH_UNINITIALIZED;
        }
        if (!$this->isInitialized()) {
            if (!$this->canBeInitialized()) {
                return self::CONTEXT_HASH_UNINITIALIZED;
            }
            $this->initialize();
        }

        if ($this->contextHash !== null) {
            return $this->contextHash;
        }

        $contextHashSoFar = implode('|', array_keys($this->getRoles()));

        $this->withoutAuthorizationChecks(function () use (&$contextHashSoFar) {
            foreach ($this->globalObjects as $globalObjectsRegisteredClassName) {
                if (is_subclass_of($globalObjectsRegisteredClassName, CacheAwareInterface::class)) {
                    $globalObject = $this->objectManager->get($globalObjectsRegisteredClassName);
                    $contextHashSoFar .= '<' . $globalObject->getCacheEntryIdentifier();
                }
            }
        });

        $this->contextHash = md5($contextHashSoFar);

        return $this->contextHash;
    }

    /**
     * returns the tag to use for sessions belonging to the given $account
     *
     * @param Account $account
     * @return string
     */
    public function getSessionTagForAccount(Account $account): string
    {
        return 'Neos-Flow-Security-Account-' . md5($account->getAccountIdentifier());
    }

    /**
     * destroys all sessions belonging to the given $account
     *
     * @param Account $account
     * @param string $reason
     * @return void
     */
    public function destroySessionsForAccount(Account $account, string $reason = ''): void
    {
        $this->sessionManager->destroySessionsByTag($this->getSessionTagForAccount($account), $reason);
    }
}
