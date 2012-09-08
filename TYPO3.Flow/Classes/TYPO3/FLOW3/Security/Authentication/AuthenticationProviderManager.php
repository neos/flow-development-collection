<?php
namespace TYPO3\FLOW3\Security\Authentication;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The default authentication manager, which relies on Authentication Providers
 * to authenticate the tokens stored in the security context.
 *
 * @FLOW3\Scope("singleton")
 */
class AuthenticationProviderManager implements \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface {

	/**
	 * @var \TYPO3\FLOW3\Log\SecurityLoggerInterface
	 * @FLOW3\Inject
	 */
	protected $securityLogger;

	/**
	 * @var \TYPO3\FLOW3\Session\SessionInterface
	 * @FLOW3\Inject
	 */
	protected $session;

	/**
	 * The provider resolver
	 *
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationProviderResolver
	 */
	protected $providerResolver;

	/**
	 * The security context of the current request
	 *
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * The request pattern resolver
	 *
	 * @var \TYPO3\FLOW3\Security\RequestPatternResolver
	 */
	protected $requestPatternResolver;

	/**
	 * Array of \TYPO3\FLOW3\Security\Authentication\AuthenticationProviderInterface objects
	 *
	 * @var array
	 */
	protected $providers = array();

	/**
	 * Array of \TYPO3\FLOW3\Security\Authentication\TokenInterface objects
	 *
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\FLOW3\Security\Authentication\AuthenticationProviderResolver $providerResolver The provider resolver
	 * @param \TYPO3\FLOW3\Security\RequestPatternResolver $requestPatternResolver The request pattern resolver
	 */
	public function __construct(AuthenticationProviderResolver $providerResolver, \TYPO3\FLOW3\Security\RequestPatternResolver $requestPatternResolver) {
		$this->providerResolver = $providerResolver;
		$this->requestPatternResolver = $requestPatternResolver;
	}

	/**
	 * Inject the settings
	 *
	 * @param array $settings The settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		if (!isset($settings['security']['authentication']['providers']) || !is_array($settings['security']['authentication']['providers'])) {
			return;
		}

		$this->buildProvidersAndTokensFromConfiguration($settings['security']['authentication']['providers']);
	}

	/**
	 * Sets the security context
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The security context of the current request
	 * @return void
	 */
	public function setSecurityContext(\TYPO3\FLOW3\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Returns the security context
	 *
	 * @return \TYPO3\FLOW3\Security\Context $securityContext The security context of the current request
	 */
	public function getSecurityContext() {
		return $this->securityContext;
	}

	/**
	 * Returns clean tokens this manager is responsible for.
	 * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
	 *
	 * @return array Array of \TYPO3\FLOW3\Security\Authentication\TokenInterface An array of tokens this manager is responsible for
	 */
	public function getTokens() {
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
	 * @throws \TYPO3\FLOW3\Security\Exception
	 * @throws \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException
	 */
	public function authenticate() {
		$anyTokenAuthenticated = FALSE;
		if ($this->securityContext === NULL) throw new \TYPO3\FLOW3\Security\Exception('Cannot authenticate because no security context has been set.', 1232978667);

		$tokens = $this->securityContext->getAuthenticationTokens();
		if (count($tokens) === 0) {
			throw new \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException('The security context contained no tokens which could be authenticated.', 1258721059);
		}

		foreach ($tokens as $token) {
			foreach ($this->providers as $provider) {
				if ($provider->canAuthenticate($token) && $token->getAuthenticationStatus() === \TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED) {
					$provider->authenticate($token);
					if ($token->isAuthenticated()) {
						$this->emitAuthenticatedToken($token);
					}
					break;
				}
			}
			if ($token->isAuthenticated()) {
				$anyTokenAuthenticated = TRUE;
				if ($this->securityContext->getAuthenticationStrategy() === \TYPO3\FLOW3\Security\Context::AUTHENTICATE_ONE_TOKEN) {
					return;
				}
			} else {
				 if ($this->securityContext->getAuthenticationStrategy() === \TYPO3\FLOW3\Security\Context::AUTHENTICATE_ALL_TOKENS) {
					throw new \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException('Could not authenticate all tokens, but authenticationStrategy was set to "all".', 1222203912);
				}
			}
		}

		if (!$anyTokenAuthenticated && $this->securityContext->getAuthenticationStrategy() !== \TYPO3\FLOW3\Security\Context::AUTHENTICATE_ANY_TOKEN) {
			throw new \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException('Could not authenticate any token. Might be missing or wrong credentials or no authentication provider matched.', 1222204027);
		}
	}

	/**
	 * Checks if there exists a user session and if at least one token is authenticated
	 *
	 * @return boolean
	 */
	public function isAuthenticated() {
		if (!$this->session->isStarted() && !$this->session->canBeResumed()) {
			return FALSE;
		}
		$atLeastOneTokenIsAuthenticated = FALSE;
		foreach ($this->securityContext->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated()) {
				$atLeastOneTokenIsAuthenticated = TRUE;
				break;
			}
		}
		return $atLeastOneTokenIsAuthenticated;
	}

	/**
	 * Logout all active authentication tokens
	 *
	 * @return void
	 */
	public function logout() {
		if ($this->isAuthenticated() !== TRUE) {
			return;
		}
		foreach ($this->securityContext->getAuthenticationTokens() as $token) {
			$token->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		}
		$this->emitLoggedOut();
	}

	/**
	 * Signals that the specified token has been successfully authenticated.
	 *
	 * @param TokenInterface $token The token which has been authenticated
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitAuthenticatedToken(TokenInterface $token) {
	}

	/**
	 * Signals that all active authentication tokens have been invalidated.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitLoggedOut() {
	}

	/**
	 * Builds the provider and token objects based on the given configuration
	 *
	 * @param array $providerConfigurations The configured provider settings
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationProviderException
	 * @throws \TYPO3\FLOW3\Security\Exception\NoEntryPointFoundException
	 */
	protected function buildProvidersAndTokensFromConfiguration(array $providerConfigurations) {
		foreach ($providerConfigurations as $providerName => $providerConfiguration) {

			if (isset($providerConfiguration['providerClass'])) {
				throw new \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" uses the deprecated option "providerClass". Check your settings and use the new option "provider" instead.', 1327672030);
			}
			if (isset($providerConfiguration['options'])) {
				throw new \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" uses the deprecated option "options". Check your settings and use the new option "providerOptions" instead.', 1327672031);
			}
			if (!is_array($providerConfiguration) || !isset($providerConfiguration['provider'])) {
				throw new \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" needs a "provider" option!', 1248209521);
			}

			$providerObjectName = $this->providerResolver->resolveProviderClass((string)$providerConfiguration['provider']);
			if ($providerObjectName === NULL) {
				throw new \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerConfiguration['provider'] . '" could not be found!', 1237330453);
			}
			$providerOptions = array();
			if (isset($providerConfiguration['providerOptions']) && is_array($providerConfiguration['providerOptions'])) {
				$providerOptions = $providerConfiguration['providerOptions'];
			}

			$providerInstance = new $providerObjectName($providerName, $providerOptions);
			$this->providers[] = $providerInstance;

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
					$requestPattern = new $patternClassName;
					$requestPattern->setPattern($patternConfiguration);
					$requestPatterns[] = $requestPattern;
				}
				$tokenInstance->setRequestPatterns($requestPatterns);
			}

			if (isset($providerConfiguration['entryPoint'])) {
				if (is_array($providerConfiguration['entryPoint'])) {
					$message = 'Invalid entry point configuration in setting "TYPO3:FLOW3:security:authentication:providers:' . $providerName .'. Check your settings and make sure to specify only one entry point for each provider.';
					throw new \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationProviderException($message, 1327671458);
				}
				$entryPointName = $providerConfiguration['entryPoint'];
				$entryPointClassName = $entryPointName;
				if (!class_exists($entryPointClassName)) {
					$entryPointClassName = 'TYPO3\FLOW3\Security\Authentication\EntryPoint\\' . $entryPointClassName;
				}
				if (!class_exists($entryPointClassName)) {
					throw new \TYPO3\FLOW3\Security\Exception\NoEntryPointFoundException('An entry point with the name: "' . $entryPointName . '" could not be resolved. Make sure it is a valid class name, either fully qualified or relative to TYPO3\FLOW3\Security\Authentication\EntryPoint!', 1236767282);
				}

				$entryPoint = new $entryPointClassName();
				if (isset($providerConfiguration['entryPointOptions'])) {
					$entryPoint->setOptions($providerConfiguration['entryPointOptions']);
				}

				$tokenInstance->setAuthenticationEntryPoint($entryPoint);
			}
		}
	}

}
?>
