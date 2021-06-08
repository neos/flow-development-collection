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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Exception;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Security\RequestPatternResolver;

/**
 * Default factory for providers and tokens.
 *
 * @Flow\Scope("singleton")
 */
class TokenAndProviderFactory implements TokenAndProviderFactoryInterface
{
    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var AuthenticationProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var TokenInterface[]
     */
    protected $tokens = [];

    /**
     * @var array
     */
    protected $providerConfigurations = [];

    /**
     * @var AuthenticationProviderResolver
     */
    protected $providerResolver;

    /**
     * @var AuthenticationTokenResolver
     */
    protected $tokenResolver;

    /**
     * @var RequestPatternResolver
     */
    protected $requestPatternResolver;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param AuthenticationProviderResolver $providerResolver The provider resolver
     * @param RequestPatternResolver $requestPatternResolver The request pattern resolver
     * @param AuthenticationTokenResolver $tokenResolver The token resolver
     */
    public function __construct(AuthenticationProviderResolver $providerResolver, RequestPatternResolver $requestPatternResolver, AuthenticationTokenResolver $tokenResolver)
    {
        $this->providerResolver = $providerResolver;
        $this->requestPatternResolver = $requestPatternResolver;
        $this->tokenResolver = $tokenResolver;
    }

    /**
     * Returns clean tokens this manager is responsible for.
     * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
     *
     * @return TokenInterface[]
     * @throws Exception\InvalidAuthenticationProviderException
     * @throws Exception\InvalidRequestPatternException
     * @throws Exception\NoAuthenticationProviderFoundException
     * @throws Exception\NoEntryPointFoundException
     * @throws Exception\NoRequestPatternFoundException
     */
    public function getTokens(): array
    {
        $this->buildProvidersAndTokensFromConfiguration();
        return $this->tokens;
    }

    /**
     * Returns all configured authentication providers
     *
     * @return AuthenticationProviderInterface[]
     * @throws Exception\InvalidAuthenticationProviderException
     * @throws Exception\InvalidRequestPatternException
     * @throws Exception\NoAuthenticationProviderFoundException
     * @throws Exception\NoEntryPointFoundException
     * @throws Exception\NoRequestPatternFoundException
     */
    public function getProviders(): array
    {
        $this->buildProvidersAndTokensFromConfiguration();
        return $this->providers;
    }

    /**
     * Inject the settings and does a fresh build of tokens based on the injected settings
     *
     * @param array $settings The settings
     * @return void
     * @throws Exception
     */
    public function injectSettings(array $settings)
    {
        if (!isset($settings['security']['authentication']['providers']) || !is_array($settings['security']['authentication']['providers'])) {
            return;
        }

        $this->providerConfigurations = $settings['security']['authentication']['providers'];
    }

    /**
     * Builds the provider and token objects based on the given configuration
     *
     * @return void
     * @throws Exception\InvalidAuthenticationProviderException
     * @throws Exception\InvalidRequestPatternException
     * @throws Exception\NoAuthenticationProviderFoundException
     * @throws Exception\NoEntryPointFoundException
     * @throws Exception\NoRequestPatternFoundException
     */
    protected function buildProvidersAndTokensFromConfiguration()
    {
        if ($this->isInitialized) {
            return;
        }

        $this->tokens = [];
        $this->providers = [];

        foreach ($this->providerConfigurations as $providerName => $providerConfiguration) {
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
            $providerInstance = $providerObjectName::create($providerName, $providerOptions);
            $this->providers[$providerName] = $providerInstance;

            /** @var $tokenInstance TokenInterface */
            $tokenInstance = null;
            foreach ($providerInstance->getTokenClassNames() as $tokenClassName) {
                if (isset($providerConfiguration['token'])) {
                    $tokenClassName = $this->tokenResolver->resolveTokenClass((string)$providerConfiguration['token']);
                }
                /** @noinspection PhpMethodParametersCountMismatchInspection */
                $tokenInstance = $this->objectManager->get($tokenClassName, $providerConfiguration['tokenOptions'] ?? []);
                if (!$tokenInstance instanceof TokenInterface) {
                    throw new Exception\InvalidAuthenticationProviderException(sprintf('The specified token is not an instance of %s but a %s. Please adjust the "token" configuration of the "%s" authentication provider', TokenInterface::class, is_object($tokenInstance) ? get_class($tokenInstance) : gettype($tokenInstance), $providerName), 1585921152);
                }
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

                    $patternType = $patternConfiguration['pattern'];
                    $patternOptions = isset($patternConfiguration['patternOptions']) ? $patternConfiguration['patternOptions'] : [];
                    $patternClassName = $this->requestPatternResolver->resolveRequestPatternClass($patternType);
                    $requestPattern = new $patternClassName($patternOptions);
                    if (!$requestPattern instanceof RequestPatternInterface) {
                        throw new Exception\InvalidRequestPatternException(sprintf('Invalid request pattern configuration in setting "Neos:Flow:security:authentication:providers:%s": Class "%s" does not implement RequestPatternInterface', $providerName, $patternClassName), 1446222774);
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

        $this->isInitialized = true;
    }
}
