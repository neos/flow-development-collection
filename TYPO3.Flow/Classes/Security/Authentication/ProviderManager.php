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
 * The default authentication manager, which uses different \F3\FLOW3\Security\Authentication\Providers
 * to authenticate the tokens stored in the security context.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ProviderManager implements \F3\FLOW3\Security\Authentication\ManagerInterface {

	/**
	 * The object manager
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * The provider resolver
	 * @var \F3\FLOW3\Security\Authentication\ProviderResolver
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
	 * Array of \F3\FLOW3\Security\Authentication\ProviderInterface objects
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
	 * @param \F3\FLOW3\Object\Manager $objectManager The object manager
	 * @param \F3\FLOW3\Security\Authentication\ProviderResolver $providerResolver The provider resolver
	 * @param \F3\FLOW3\Security\RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @param \F3\FLOW3\Security\Authentication\EntryPointResolver $entryPointResolver The authentication entry point resolver
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager,
			\F3\FLOW3\Security\Authentication\ProviderResolver $providerResolver,
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
	 * If securityContext->authenticateAllTokens() returns TRUE all tokens have be authenticated,
	 * otherwise there has to be at least one authenticated token to have a valid authentication.
	 *
	 * Note: This method sets the 'authenticationPerformed' flag in the security context. You have to
	 * set it back to FALSE if you need reauthentication (usually the tokens should do it as soon as they
	 * received new credentials).
	 *
	 * @return void
	 * @throws \F3\FLOW3\Security\Exception\AuthenticationRequired
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate() {
		$allTokensAreAuthenticated = TRUE;
		if ($this->securityContext === NULL) throw new \F3\FLOW3\Security\Exception('Cannot authenticate because no security context has been set.', 1232978667);

		foreach ($this->securityContext->getAuthenticationTokens() as $token) {
			foreach ($this->providers as $provider) {
				if ($provider->canAuthenticate($token)
					&& $token->getAuthenticationStatus() === \F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED) {

					$provider->authenticate($token);
					break;
				}
			}

			if ($token->isAuthenticated() && !$this->securityContext->authenticateAllTokens()) return;
			if (!$token->isAuthenticated() && $this->securityContext->authenticateAllTokens()) throw new \F3\FLOW3\Security\Exception\AuthenticationRequired('Could not authenticate all tokens, but authenticateAllTokens was set to TRUE.', 1222203912);
			$allTokensAreAuthenticated &= $token->isAuthenticated();
		}

		if ($allTokensAreAuthenticated) return;

		throw new \F3\FLOW3\Security\Exception\AuthenticationRequired('Could not authenticate any token.', 1222204027);
	}

	/**
	 * Builds the provider and token objects based on the given configuration
	 *
	 * @param array $providers The configured provider settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildProvidersAndTokensFromConfiguration(array $providers) {
		foreach ($providers as $providerName => $providerConfiguration) {

			if (!is_array($providerConfiguration) || !isset($providerConfiguration['providerClass'])) throw new \F3\FLOW3\Security\Exception\InvalidAuthenticationProvider('The configured authentication provider "' . $providerConfiguration['providerClass'] . '" could not be found!', 1248209521);

			$providerObjectName = $this->providerResolver->resolveProviderClass((string)$providerConfiguration['providerClass']);
			if ($providerObjectName === NULL) throw new \F3\FLOW3\Security\Exception\InvalidAuthenticationProvider('The configured authentication provider "' . $providerConfiguration['providerClass'] . '" could not be found!', 1237330453);

			$providerOptions = array();
			if (isset($providerConfiguration['options']) && is_array($providerConfiguration['options'])) $providerOptions = $providerConfiguration['options'];

			$providerInstance = $this->objectManager->getObject($providerObjectName, $providerName, $providerOptions);
			$this->providers[] = $providerInstance;

			foreach ($providerInstance->getTokenClassNames() as $tokenClassName) {
				$tokenInstance = $this->objectManager->getObject($tokenClassName);
				$tokenInstance->setAuthenticationProviderName($providerName);
				$this->tokens[] = $tokenInstance;
			}

			if (isset($providerConfiguration['requestPatterns']) && is_array($providerConfiguration['requestPatterns'])) {
				$requestPatterns = array();
				foreach($providerConfiguration['requestPatterns'] as $patternType => $patternConfiguration) {
					$requestPattern = $this->objectManager->getObject($this->requestPatternResolver->resolveRequestPatternClass($patternType));
					$requestPattern->setPattern($patternConfiguration);
					$requestPatterns[] = $requestPattern;
				}
				$tokenInstance->setRequestPatterns($requestPatterns);
			}

			if (isset($providerConfiguration['entryPoint']) && is_array($providerConfiguration['entryPoint'])) {
				reset($providerConfiguration['entryPoint']);
				$entryPointObjectName = key($providerConfiguration['entryPoint']);

				$entryPoint = $this->objectManager->getObject($this->entryPointResolver->resolveEntryPointClass($entryPointObjectName));
				$entryPoint->setOptions($providerConfiguration['entryPoint'][$entryPointObjectName]);

				$tokenInstance->setAuthenticationEntryPoint($entryPoint);
			}
		}
	}
}

?>