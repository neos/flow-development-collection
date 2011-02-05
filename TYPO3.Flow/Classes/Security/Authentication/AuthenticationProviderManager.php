<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
 * The default authentication manager, which relies on Authentication Providers
 * to authenticate the tokens stored in the security context.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AuthenticationProviderManager implements \F3\FLOW3\Security\Authentication\AuthenticationManagerInterface {

	/**
	 * @var \F3\FLOW3\Log\SecurityLoggerInterface
	 */
	protected $securityLogger;

	/**
	 * @param \F3\FLOW3\Log\SecurityLoggerInterface $securityLogger
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSecurityLogger(\F3\FLOW3\Log\SecurityLoggerInterface $securityLogger) {
		$this->securityLogger = $securityLogger;
	}

	/**
	 * The object manager
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The provider resolver
	 * @var \F3\FLOW3\Security\Authentication\AuthenticationProviderResolver
	 */
	protected $providerResolver;

	/**
	 * The security context of the current request
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * The request pattern resolver
	 * @var \F3\FLOW3\Security\RequestPatternResolver
	 */
	protected $requestPatternResolver;

	/**
	 * The authentication entry point resolver
	 * @var \F3\FLOW3\Security\Authentication\EntryPointResolver
	 */
	protected $entryPointResolver;

	/**
	 * Array of \F3\FLOW3\Security\Authentication\AuthenticationProviderInterface objects
	 * @var array
	 */
	protected $providers = array();

	/**
	 * Array of \F3\FLOW3\Security\Authentication\TokenInterface objects
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager The object manager
	 * @param \F3\FLOW3\Security\Authentication\AuthenticationProviderResolver $providerResolver The provider resolver
	 * @param \F3\FLOW3\Security\RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @param \F3\FLOW3\Security\Authentication\EntryPointResolver $entryPointResolver The authentication entry point resolver
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Object\ObjectManagerInterface $objectManager,
			\F3\FLOW3\Security\Authentication\AuthenticationProviderResolver $providerResolver,
			\F3\FLOW3\Security\RequestPatternResolver $requestPatternResolver,
			\F3\FLOW3\Security\Authentication\EntryPointResolver $entryPointResolver) {
		$this->objectManager = $objectManager;
		$this->providerResolver = $providerResolver;
		$this->requestPatternResolver = $requestPatternResolver;
		$this->entryPointResolver = $entryPointResolver;
	}

	/**
	 * Inject the settings
	 *
	 * @param array $settings The settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings(array $settings) {
		if (!isset($settings['security']['authentication']['providers'])) return;
		if (!is_array($settings['security']['authentication']['providers'])) return;

		$this->buildProvidersAndTokensFromConfiguration($settings['security']['authentication']['providers']);
	}

	/**
	 * Sets the security context
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The security context of the current request
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setSecurityContext(\F3\FLOW3\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Returns the security context
	 *
	 * @return \F3\FLOW3\Security\Context $securityContext The security context of the current request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSecurityContext() {
		return $this->securityContext;
	}

	/**
	 * Returns clean tokens this manager is responsible for.
	 * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
	 *
	 * @return array Array of \F3\FLOW3\Security\Authentication\TokenInterface An array of tokens this manager is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @throws \F3\FLOW3\Security\Exception\AuthenticationRequiredException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate() {
		$anyTokenAuthenticated = FALSE;
		if ($this->securityContext === NULL) throw new \F3\FLOW3\Security\Exception('Cannot authenticate because no security context has been set.', 1232978667);

		$tokens = $this->securityContext->getAuthenticationTokens();
		if (count($tokens) === 0) {
			throw new \F3\FLOW3\Security\Exception\AuthenticationRequiredException('The security context contained no tokens which could be authenticated.', 1258721059);
		}

		foreach ($tokens as $token) {
			foreach ($this->providers as $provider) {
				if ($provider->canAuthenticate($token) && $token->getAuthenticationStatus() === \F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED) {
					$provider->authenticate($token);
					break;
				}
			}
			if ($token->isAuthenticated()) {
				$anyTokenAuthenticated = TRUE;
				if ($this->securityContext->getAuthenticationStrategy() === \F3\FLOW3\Security\Context::AUTHENTICATE_ONE_TOKEN) {
					return;
				}
			} else {
				 if ($this->securityContext->getAuthenticationStrategy() === \F3\FLOW3\Security\Context::AUTHENTICATE_ALL_TOKENS) {
					throw new \F3\FLOW3\Security\Exception\AuthenticationRequiredException('Could not authenticate all tokens, but authenticationStrategy was set to "all".', 1222203912);
				}
			}
		}

		if (!$anyTokenAuthenticated && $this->securityContext->getAuthenticationStrategy() !== \F3\FLOW3\Security\Context::AUTHENTICATE_ANY_TOKEN) {
			throw new \F3\FLOW3\Security\Exception\AuthenticationRequiredException('Could not authenticate any token. Might be missing or wrong credentials or no authentication provider matched.', 1222204027);
		}
	}

	/**
	 * Logout all active authentication tokens
	 *
	 * @return void
	 */
	public function logout() {
		foreach ($this->securityContext->getAuthenticationTokens() as $token) {
			$token->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}

	/**
	 * Builds the provider and token objects based on the given configuration
	 *
	 * @param array $providerConfigurations The configured provider settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildProvidersAndTokensFromConfiguration(array $providerConfigurations) {
		foreach ($providerConfigurations as $providerName => $providerConfiguration) {

			if (!is_array($providerConfiguration) || !isset($providerConfiguration['providerClass'])) throw new \F3\FLOW3\Security\Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerConfiguration['providerClass'] . '" could not be found!', 1248209521);

			$providerObjectName = $this->providerResolver->resolveProviderClass((string)$providerConfiguration['providerClass']);
			if ($providerObjectName === NULL) throw new \F3\FLOW3\Security\Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerConfiguration['providerClass'] . '" could not be found!', 1237330453);
			$providerOptions = array();
			if (isset($providerConfiguration['options']) && is_array($providerConfiguration['options'])) $providerOptions = $providerConfiguration['options'];

			$providerInstance = $this->objectManager->get($providerObjectName, $providerName, $providerOptions);
			$this->providers[] = $providerInstance;

			foreach ($providerInstance->getTokenClassNames() as $tokenClassName) {
				if (isset($providerConfiguration['tokenClass']) && $providerConfiguration['tokenClass'] !== $tokenClassName) continue;
				$tokenInstance = $this->objectManager->get($tokenClassName);
				$tokenInstance->setAuthenticationProviderName($providerName);
				$this->tokens[] = $tokenInstance;
				break;
			}

			if (isset($providerConfiguration['requestPatterns']) && is_array($providerConfiguration['requestPatterns'])) {
				$requestPatterns = array();
				foreach($providerConfiguration['requestPatterns'] as $patternType => $patternConfiguration) {
					$requestPattern = $this->objectManager->get($this->requestPatternResolver->resolveRequestPatternClass($patternType));
					$requestPattern->setPattern($patternConfiguration);
					$requestPatterns[] = $requestPattern;
				}
				$tokenInstance->setRequestPatterns($requestPatterns);
			}

			if (isset($providerConfiguration['entryPoint']) && is_array($providerConfiguration['entryPoint'])) {
				reset($providerConfiguration['entryPoint']);
				$entryPointObjectName = key($providerConfiguration['entryPoint']);

				$entryPoint = $this->objectManager->get($this->entryPointResolver->resolveEntryPointClass($entryPointObjectName));
				$entryPoint->setOptions($providerConfiguration['entryPoint'][$entryPointObjectName]);

				$tokenInstance->setAuthenticationEntryPoint($entryPoint);
			}
		}
	}
}

?>