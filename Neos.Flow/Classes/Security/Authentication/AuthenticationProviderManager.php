<?php
namespace Neos\Flow\Security\Authentication;

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
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\SessionManagerInterface;

/**
 * The default authentication manager, which relies on Authentication Providers
 * to authenticate the tokens stored in the security context.
 *
 * @Flow\Scope("singleton")
 */
class AuthenticationProviderManager implements AuthenticationManagerInterface
{
    /**
     * @var SessionManagerInterface
     * @Flow\Inject
     */
    protected $sessionManager;

    /**
     * @var TokenAndProviderFactoryInterface
     */
    protected $tokenAndProviderFactory;

    /**
     * The security context of the current request
     *
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * Injected configuration for providers.
     * Will be null'd again after building the object instances.
     *
     * @var array|null
     */
    protected $providerConfigurations;

    /**
     * @var bool
     */
    protected $isAuthenticated;

    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var string
     */
    protected $authenticationStrategy;

    /**
     * @param TokenAndProviderFactoryInterface $tokenAndProviderFactory
     */
    public function __construct(TokenAndProviderFactoryInterface $tokenAndProviderFactory)
    {
        $this->tokenAndProviderFactory = $tokenAndProviderFactory;
    }

    /**
     * Inject the settings and does a fresh build of tokens based on the injected settings
     *
     * @param array $settings The settings
     * @return void
     * @throws Exception
     */
    public function injectSettings(array $settings): void
    {
        if (isset($settings['security']['authentication']['authenticationStrategy'])) {
            $authenticationStrategyName = $settings['security']['authentication']['authenticationStrategy'];
            switch ($authenticationStrategyName) {
                case 'allTokens':
                    $this->authenticationStrategy = Context::AUTHENTICATE_ALL_TOKENS;
                    break;
                case 'oneToken':
                    $this->authenticationStrategy = Context::AUTHENTICATE_ONE_TOKEN;
                    break;
                case 'atLeastOneToken':
                    $this->authenticationStrategy = Context::AUTHENTICATE_AT_LEAST_ONE_TOKEN;
                    break;
                case 'anyToken':
                    $this->authenticationStrategy = Context::AUTHENTICATE_ANY_TOKEN;
                    break;
                default:
                    throw new Exception('Invalid setting "' . $authenticationStrategyName . '" for security.authentication.authenticationStrategy', 1291043022);
            }
        }
    }

    /**
     * Returns the security context
     *
     * @return Context $securityContext The security context of the current request
     */
    public function getSecurityContext(): Context
    {
        return $this->securityContext;
    }

    /**
     * Tries to authenticate the tokens in the security context (in the given order)
     * with the available authentication providers, if needed.
     * If the authentication strategy is set to "allTokens", all tokens have to be authenticated.
     * If the strategy is set to "oneToken", only one token needs to be authenticated, but the
     * authentication will stop after the first authenticated token. The strategy
     * "atLeastOne" will try to authenticate at least one and as many tokens as possible.
     *
     * @return void
     * @throws Exception
     * @throws AuthenticationRequiredException
     * @throws NoTokensAuthenticatedException
     */
    public function authenticate(): void
    {
        $this->isAuthenticated = false;
        $anyTokenAuthenticated = false;

        if ($this->securityContext === null) {
            throw new Exception('Cannot authenticate because no security context has been set.', 1232978667);
        }

        $tokens = $this->securityContext->getAuthenticationTokens();
        if (count($tokens) === 0) {
            throw new NoTokensAuthenticatedException('The security context contained no tokens which could be authenticated.', 1258721059);
        }

        $session = $this->sessionManager->getCurrentSession();

        /** @var $token TokenInterface */
        foreach ($tokens as $token) {
            /** @var $provider AuthenticationProviderInterface */
            foreach ($this->tokenAndProviderFactory->getProviders() as $provider) {
                if ($provider->canAuthenticate($token) && $token->getAuthenticationStatus() === TokenInterface::AUTHENTICATION_NEEDED) {
                    $provider->authenticate($token);
                    if ($token->isAuthenticated()) {
                        $this->emitAuthenticatedToken($token);
                    }
                    break;
                }
            }
            if ($token->isAuthenticated()) {
                if (!$token instanceof SessionlessTokenInterface) {
                    if (!$session->isStarted()) {
                        $session->start();
                    }
                    $account = $token->getAccount();
                    if ($account !== null) {
                        $this->securityContext->withoutAuthorizationChecks(function () use ($account, $session) {
                            $session->addTag($this->securityContext->getSessionTagForAccount($account));
                        });
                    }
                }
                if ($this->authenticationStrategy === Context::AUTHENTICATE_ONE_TOKEN) {
                    $this->isAuthenticated = true;
                    $this->emitSuccessfullyAuthenticated();
                    return;
                }
                $anyTokenAuthenticated = true;
            } else {
                if ($this->authenticationStrategy === Context::AUTHENTICATE_ALL_TOKENS) {
                    throw new AuthenticationRequiredException('Could not authenticate all tokens, but authenticationStrategy was set to "all".', 1222203912);
                }
            }
        }

        if (!$anyTokenAuthenticated && $this->authenticationStrategy !== Context::AUTHENTICATE_ANY_TOKEN) {
            throw new NoTokensAuthenticatedException('Could not authenticate any token. Might be missing or wrong credentials or no authentication provider matched.', 1222204027);
        }

        $this->isAuthenticated = $anyTokenAuthenticated;
        $this->emitSuccessfullyAuthenticated();
    }

    /**
     * Checks if one or all tokens are authenticated (depending on the authentication strategy).
     *
     * Will call authenticate() if not done before.
     *
     * @return boolean
     * @throws Exception
     */
    public function isAuthenticated(): bool
    {
        if ($this->isAuthenticated === null) {
            try {
                $this->authenticate();
            } catch (AuthenticationRequiredException $exception) {
            }
        }
        return $this->isAuthenticated;
    }

    /**
     * Logout all active authentication tokens
     *
     * @return void
     * @throws Exception
     * @throws SessionNotStartedException
     */
    public function logout(): void
    {
        if ($this->isAuthenticated() !== true) {
            return;
        }
        $this->isAuthenticated = null;
        $session = $this->sessionManager->getCurrentSession();

        /** @var $token TokenInterface */
        foreach ($this->securityContext->getAuthenticationTokens() as $token) {
            $token->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
        }
        $this->emitLoggedOut();
        if ($session->isStarted()) {
            $session->destroy('Logout through AuthenticationProviderManager');
        }
    }

    /**
     * Signals that the specified token has been successfully authenticated.
     *
     * @param TokenInterface $token The token which has been authenticated
     * @return void
     * @Flow\Signal
     */
    protected function emitAuthenticatedToken(TokenInterface $token): void
    {
    }

    /**
     * Signals that all active authentication tokens have been invalidated.
     * Note: the session will be destroyed after this signal has been emitted.
     *
     * @return void
     * @Flow\Signal
     */
    protected function emitLoggedOut(): void
    {
    }

    /**
     * Signals that authentication commenced and at least one token was authenticated.
     *
     * @return void
     * @Flow\Signal
     */
    protected function emitSuccessfullyAuthenticated(): void
    {
    }
}
