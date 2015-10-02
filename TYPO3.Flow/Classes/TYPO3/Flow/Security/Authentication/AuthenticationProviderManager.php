<?php
namespace TYPO3\Flow\Security\Authentication;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Exception\NoTokensAuthenticatedException;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;
use TYPO3\Flow\Security\Exception;
use TYPO3\Flow\Security\RequestPatternResolver;

/**
 * The default authentication manager, which relies on Authentication Providers
 * to authenticate the tokens stored in the security context.
 *
 * @Flow\Scope("singleton")
 */
class AuthenticationProviderManager implements AuthenticationManagerInterface
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SecurityLoggerInterface
     */
    protected $securityLogger;

    /**
     * @var \TYPO3\Flow\Session\SessionInterface
     * @Flow\Inject
     */
    protected $session;

    /**
     * The provider resolver
     *
     * @var \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver
     */
    protected $providerResolver;

    /**
     * The security context of the current request
     *
     * @var \TYPO3\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * The request pattern resolver
     *
     * @var \TYPO3\Flow\Security\RequestPatternResolver
     */
    protected $requestPatternResolver;

    /**
     * Array of \TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface objects
     *
     * @var array
     */
    protected $providers = array();

    /**
     * Array of \TYPO3\Flow\Security\Authentication\TokenInterface objects
     *
     * @var array
     */
    protected $tokens = array();

    /**
     * @var boolean
     */
    protected $isAuthenticated = null;

    /**
     * Constructor.
     *
     * @param \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver $providerResolver The provider resolver
     * @param \TYPO3\Flow\Security\RequestPatternResolver $requestPatternResolver The request pattern resolver
     */
    public function __construct(AuthenticationProviderResolver $providerResolver, RequestPatternResolver $requestPatternResolver)
    {
        $this->providerResolver = $providerResolver;
        $this->requestPatternResolver = $requestPatternResolver;
    }

    /**
     * Inject the settings and does a fresh build of tokens based on the injected settings
     *
     * @param array $settings The settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        if (!isset($settings['security']['authentication']['providers']) || !is_array($settings['security']['authentication']['providers'])) {
            return;
        }

        $this->buildProvidersAndTokensFromConfiguration($settings['security']['authentication']['providers']);
    }

    /**
     * Sets the security context
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The security context of the current request
     * @return void
     */
    public function setSecurityContext(Context $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Returns the security context
     *
     * @return \TYPO3\Flow\Security\Context $securityContext The security context of the current request
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * Returns clean tokens this manager is responsible for.
     * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
     *
     * @return array Array of \TYPO3\Flow\Security\Authentication\TokenInterface An array of tokens this manager is responsible for
     */
    public function getTokens()
    {
        return $this->tokens;
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
     * @throws \TYPO3\Flow\Security\Exception
     * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
     */
    public function authenticate()
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

        /** @var $token TokenInterface */
        foreach ($tokens as $token) {
            /** @var $provider \TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface */
            foreach ($this->providers as $providerName => $provider) {
                if ($provider->canAuthenticate($token) && $token->getAuthenticationStatus() === TokenInterface::AUTHENTICATION_NEEDED) {
                    $provider->authenticate($token);
                    if ($token->isAuthenticated()) {
                        $this->emitAuthenticatedToken($token);
                    }
                    break;
                }
            }
            if ($token->isAuthenticated()) {
                if (!$token instanceof SessionlessTokenInterface && !$this->session->isStarted()) {
                    $this->session->start();
                }
                if ($this->securityContext->getAuthenticationStrategy() === Context::AUTHENTICATE_ONE_TOKEN) {
                    $this->isAuthenticated = true;
                    return;
                }
                $anyTokenAuthenticated = true;
            } else {
                if ($this->securityContext->getAuthenticationStrategy() === Context::AUTHENTICATE_ALL_TOKENS) {
                    throw new AuthenticationRequiredException('Could not authenticate all tokens, but authenticationStrategy was set to "all".', 1222203912);
                }
            }
        }

        if (!$anyTokenAuthenticated && $this->securityContext->getAuthenticationStrategy() !== Context::AUTHENTICATE_ANY_TOKEN) {
            throw new NoTokensAuthenticatedException('Could not authenticate any token. Might be missing or wrong credentials or no authentication provider matched.', 1222204027);
        }

        $this->isAuthenticated = $anyTokenAuthenticated;
    }

    /**
     * Checks if one or all tokens are authenticated (depending on the authentication strategy).
     *
     * Will call authenticate() if not done before.
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        if ($this->isAuthenticated === null) {
            try {
                $this->authenticate();
            } catch (AuthenticationRequiredException $e) {
            }
        }
        return $this->isAuthenticated;
    }

    /**
     * Logout all active authentication tokens
     *
     * @return void
     */
    public function logout()
    {
        if ($this->isAuthenticated() !== true) {
            return;
        }
        $this->isAuthenticated = null;
        /** @var $token TokenInterface */
        foreach ($this->securityContext->getAuthenticationTokens() as $token) {
            $token->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
        }
        $this->emitLoggedOut();
        if ($this->session->isStarted()) {
            $this->session->destroy('Logout through AuthenticationProviderManager');
        }
    }

    /**
     * Signals that the specified token has been successfully authenticated.
     *
     * @param TokenInterface $token The token which has been authenticated
     * @return void
     * @Flow\Signal
     */
    protected function emitAuthenticatedToken(TokenInterface $token)
    {
    }

    /**
     * Signals that all active authentication tokens have been invalidated.
     * Note: the session will be destroyed after this signal has been emitted.
     *
     * @return void
     * @Flow\Signal
     */
    protected function emitLoggedOut()
    {
    }

    /**
     * Builds the provider and token objects based on the given configuration
     *
     * @param array $providerConfigurations The configured provider settings
     * @return void
     * @throws Exception\InvalidAuthenticationProviderException
     * @throws Exception\NoEntryPointFoundException
     */
    protected function buildProvidersAndTokensFromConfiguration(array $providerConfigurations)
    {
        foreach ($providerConfigurations as $providerName => $providerConfiguration) {
            if (isset($providerConfiguration['providerClass'])) {
                throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" uses the deprecated option "providerClass". Check your settings and use the new option "provider" instead.', 1327672030);
            }
            if (isset($providerConfiguration['options'])) {
                throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" uses the deprecated option "options". Check your settings and use the new option "providerOptions" instead.', 1327672031);
            }
            if (!is_array($providerConfiguration) || !isset($providerConfiguration['provider'])) {
                throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" needs a "provider" option!', 1248209521);
            }

            $providerObjectName = $this->providerResolver->resolveProviderClass((string)$providerConfiguration['provider']);
            if ($providerObjectName === null) {
                throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerConfiguration['provider'] . '" could not be found!', 1237330453);
            }
            $providerOptions = array();
            if (isset($providerConfiguration['providerOptions']) && is_array($providerConfiguration['providerOptions'])) {
                $providerOptions = $providerConfiguration['providerOptions'];
            }

            /** @var $providerInstance \TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface */
            $providerInstance = new $providerObjectName($providerName, $providerOptions);
            $this->providers[$providerName] = $providerInstance;

            /** @var $tokenInstance TokenInterface */
            $tokenInstance = null;
            foreach ($providerInstance->getTokenClassNames() as $tokenClassName) {
                if (isset($providerConfiguration['token']) && $providerConfiguration['token'] !== $tokenClassName) {
                    continue;
                }

                $tokenInstance = new $tokenClassName();
                $tokenInstance->setAuthenticationProviderName($providerName);
                $this->tokens[] = $tokenInstance;
                break;
            }

            if (isset($providerConfiguration['requestPatterns']) && is_array($providerConfiguration['requestPatterns'])) {
                $requestPatterns = array();
                foreach ($providerConfiguration['requestPatterns'] as $patternType => $patternConfiguration) {
                    $patternClassName = $this->requestPatternResolver->resolveRequestPatternClass($patternType);
                    /** @var $requestPattern \TYPO3\Flow\Security\RequestPatternInterface */
                    $requestPattern = new $patternClassName;
                    $requestPattern->setPattern($patternConfiguration);
                    $requestPatterns[] = $requestPattern;
                }
                if ($tokenInstance !== null) {
                    $tokenInstance->setRequestPatterns($requestPatterns);
                }
            }

            if (isset($providerConfiguration['entryPoint'])) {
                if (is_array($providerConfiguration['entryPoint'])) {
                    $message = 'Invalid entry point configuration in setting "TYPO3:Flow:security:authentication:providers:' . $providerName . '. Check your settings and make sure to specify only one entry point for each provider.';
                    throw new Exception\InvalidAuthenticationProviderException($message, 1327671458);
                }
                $entryPointName = $providerConfiguration['entryPoint'];
                $entryPointClassName = $entryPointName;
                if (!class_exists($entryPointClassName)) {
                    $entryPointClassName = 'TYPO3\Flow\Security\Authentication\EntryPoint\\' . $entryPointClassName;
                }
                if (!class_exists($entryPointClassName)) {
                    throw new Exception\NoEntryPointFoundException('An entry point with the name: "' . $entryPointName . '" could not be resolved. Make sure it is a valid class name, either fully qualified or relative to TYPO3\Flow\Security\Authentication\EntryPoint!', 1236767282);
                }

                /** @var $entryPoint \TYPO3\Flow\Security\Authentication\EntryPointInterface */
                $entryPoint = new $entryPointClassName();
                if (isset($providerConfiguration['entryPointOptions'])) {
                    $entryPoint->setOptions($providerConfiguration['entryPointOptions']);
                }

                $tokenInstance->setAuthenticationEntryPoint($entryPoint);
            }
        }
    }
}
