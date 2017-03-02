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
use Neos\Flow\Log\SecurityLoggerInterface;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Security\RequestPatternResolver;
use Neos\Flow\Session\SessionInterface;

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
     * @var SecurityLoggerInterface
     */
    protected $securityLogger;

    /**
     * @var SessionInterface
     * @Flow\Inject
     */
    protected $session;

    /**
     * The provider resolver
     *
     * @var AuthenticationProviderResolver
     */
    protected $providerResolver;

    /**
     * The security context of the current request
     *
     * @var Context
     */
    protected $securityContext;

    /**
     * The request pattern resolver
     *
     * @var RequestPatternResolver
     */
    protected $requestPatternResolver;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * @var boolean
     */
    protected $isAuthenticated = null;

    /**
     * @param AuthenticationProviderResolver $providerResolver The provider resolver
     * @param RequestPatternResolver $requestPatternResolver The request pattern resolver
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
     * @param Context $securityContext The security context of the current request
     * @return void
     */
    public function setSecurityContext(Context $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Returns the security context
     *
     * @return Context $securityContext The security context of the current request
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * Returns clean tokens this manager is responsible for.
     * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
     *
     * @return array Array of TokenInterface this manager is responsible for
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Returns all configured authentication providers
     *
     * @return array Array of \Neos\Flow\Security\Authentication\AuthenticationProviderInterface
     */
    public function getProviders()
    {
        return $this->providers;
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
            /** @var $provider AuthenticationProviderInterface */
            foreach ($this->providers as $provider) {
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
                    if (!$this->session->isStarted()) {
                        $this->session->start();
                    }
                    $account = $token->getAccount();
                    if ($account !== null) {
                        $this->securityContext->withoutAuthorizationChecks(function () use ($account) {
                            $this->session->addTag('Neos-Flow-Security-Account-' . md5($account->getAccountIdentifier()));
                        });
                    }
                }
                if ($this->securityContext->getAuthenticationStrategy() === Context::AUTHENTICATE_ONE_TOKEN) {
                    $this->isAuthenticated = true;
                    $this->securityContext->refreshRoles();
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
        $this->securityContext->refreshRoles();
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
            } catch (AuthenticationRequiredException $exception) {
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
        $this->securityContext->refreshTokens();
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
            $providerOptions = [];
            if (isset($providerConfiguration['providerOptions']) && is_array($providerConfiguration['providerOptions'])) {
                $providerOptions = $providerConfiguration['providerOptions'];
            }

            /** @var $providerInstance AuthenticationProviderInterface */
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
                $requestPatterns = [];
                foreach ($providerConfiguration['requestPatterns'] as $patternName => $patternConfiguration) {
                    // skip request patterns that are set to NULL (i.e. `somePattern: ~` in a YAML file)
                    if ($patternConfiguration === null) {
                        continue;
                    }

                    // The following check is needed for backwards compatibility:
                    // Previously the request pattern configuration was just a key/value where the value was passed to the setPattern() method
                    if (is_string($patternConfiguration)) {
                        $patternType = $patternName;
                        $patternOptions = [];
                    } else {
                        $patternType = $patternConfiguration['pattern'];
                        $patternOptions = isset($patternConfiguration['patternOptions']) ? $patternConfiguration['patternOptions'] : [];
                    }
                    $patternClassName = $this->requestPatternResolver->resolveRequestPatternClass($patternType);
                    $requestPattern = new $patternClassName($patternOptions);
                    if (!$requestPattern instanceof RequestPatternInterface) {
                        throw new Exception\InvalidRequestPatternException(sprintf('Invalid request pattern configuration in setting "Neos:Flow:security:authentication:providers:%s": Class "%s" does not implement RequestPatternInterface', $providerName, $patternClassName), 1446222774);
                    }

                    // The following check needed for backwards compatibility:
                    // Previously each pattern had only one option that was set via the setPattern() method. Now options are passed to the constructor.
                    if (is_string($patternConfiguration) && is_callable([$requestPattern, 'setPattern'])) {
                        $requestPattern->setPattern($patternConfiguration);
                    }
                    $requestPatterns[] = $requestPattern;
                }
                if ($tokenInstance !== null) {
                    $tokenInstance->setRequestPatterns($requestPatterns);
                }
            }

            if (isset($providerConfiguration['entryPoint'])) {
                if (is_array($providerConfiguration['entryPoint'])) {
                    $message = 'Invalid entry point configuration in setting "Neos:Flow:security:authentication:providers:' . $providerName . '. Check your settings and make sure to specify only one entry point for each provider.';
                    throw new Exception\InvalidAuthenticationProviderException($message, 1327671458);
                }
                $entryPointName = $providerConfiguration['entryPoint'];
                $entryPointClassName = $entryPointName;
                if (!class_exists($entryPointClassName)) {
                    $entryPointClassName = 'Neos\Flow\Security\Authentication\EntryPoint\\' . $entryPointClassName;
                }
                if (!class_exists($entryPointClassName)) {
                    throw new Exception\NoEntryPointFoundException('An entry point with the name: "' . $entryPointName . '" could not be resolved. Make sure it is a valid class name, either fully qualified or relative to Neos\Flow\Security\Authentication\EntryPoint!', 1236767282);
                }

                /** @var $entryPoint EntryPointInterface */
                $entryPoint = new $entryPointClassName();
                if (isset($providerConfiguration['entryPointOptions'])) {
                    $entryPoint->setOptions($providerConfiguration['entryPointOptions']);
                }

                $tokenInstance->setAuthenticationEntryPoint($entryPoint);
            }
        }
    }
}
