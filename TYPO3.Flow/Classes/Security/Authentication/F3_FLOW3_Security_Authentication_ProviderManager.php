<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authentication;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * The default authentication manager, which uses different F3::FLOW3::Security::Authentication::Providers
 * to authenticate the tokens stored in the security context.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ProviderManager implements F3::FLOW3::Security::Authentication::ManagerInterface {

	/**
	 * @var F3::FLOW3::Component::ManagerInterface The component manager
	 */
	protected $componentManager;

	/**
	 * @var F3::FLOW3::Security::Authentication::ProviderResolver The provider resolver
	 */
	protected $providerResolver;

	/**
	 * The security context of the current request
	 * @var F3::FLOW3::Security::Context
	 */
	protected $securityContext;

	/**
	 * @var F3::FLOW3::Security::RequestPatternResolver The request pattern resolver
	 */
	protected $requestPatternResolver;

	/**
	 * @var array Array of F3::FLOW3::Security::Authentication::ProviderInterface objects
	 */
	protected $providers = array();

	/**
	 * @var array Array of F3::FLOW3::Security::Authentication::TokenInterface objects
	 */
	protected $tokens = array();

	/**
	 * Constructor.
	 *
	 * @param F3::FLOW3::Configuration::Manager $configurationManager The configuration manager
	 * @param F3::FLOW3::Component::Manager $componentManager The component manager
	 * @param F3::FLOW3::Security::Authentication::ProviderResolver $providerResolver The provider resolver
	 * @param F3::FLOW3::Security::RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Configuration::Manager $configurationManager,
			F3::FLOW3::Component::ManagerInterface $componentManager,
			F3::FLOW3::Security::Authentication::ProviderResolver $providerResolver,
			F3::FLOW3::Security::RequestPatternResolver $requestPatternResolver) {

		$this->componentManager = $componentManager;
		$this->providerResolver = $providerResolver;
		$this->requestPatternResolver = $requestPatternResolver;

		$this->buildProvidersAndTokensFromConfiguration($configurationManager->getSettings('FLOW3'));
	}

	/**
	 * Sets the providers
	 *
	 * @param array Array of providers (F3::FLOW3::Security::Authentication::ProviderInterface)
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setProviders($providers) {
		$this->providers = $providers;
	}

	/**
	 * Sets the security context
	 *
	 * @param F3::FLOW3::Security::Context $securityContext The security context of the current request
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setSecurityContext(F3::FLOW3::Security::Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Returns the configured providers
	 *
	 * @return array Array of configured providers
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getProviders() {
		return $this->providers;
	}

	/**
	 * Returns clean tokens this manager is responsible for.
	 * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
	 *
	 * @return array Array of F3::FLOW3::Security::Authentication::TokenInterface An array of tokens this manager is responsible for
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
	 * @return void
	 * @throws F3::FLOW3::Security::Exception::AuthenticationRequired
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate() {
		$allTokensAreAuthenticated = TRUE;
		foreach ($this->securityContext->getAuthenticationTokens() as $token) {
			foreach ($this->providers as $provider) {
				if ($provider->canAuthenticate($token)) {
					$provider->authenticate($token);
					break;
				}
			}

			if ($token->isAuthenticated() && !$this->securityContext->authenticateAllTokens()) return;
			if (!$token->isAuthenticated() && $this->securityContext->authenticateAllTokens()) throw new F3::FLOW3::Security::Exception::AuthenticationRequired('Could not authenticate all tokens, but authenticateAllTokens was set to TRUE.', 1222203912);
			$allTokensAreAuthenticated &= $token->isAuthenticated();
		}

		$this->securityContext->setAuthenticationPerformed(TRUE);
		if ($allTokensAreAuthenticated) return;

		throw new F3::FLOW3::Security::Exception::AuthenticationRequired('Could not authenticate any token.', 1222204027);
	}

	/**
	 * Builds the provider and token objects based on the given configuration
	 *
	 * @param array The FLOW3 settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo resolve and set authentication entry point and user details service in the tokens
	 */
	protected function buildProvidersAndTokensFromConfiguration(array $settings) {
		foreach ($settings['security']['authentication']['providers'] as $provider) {
			$providerInstance = $this->componentManager->getComponent($this->providerResolver->resolveProviderClass($provider['provider']));
			$this->providers[] = $providerInstance;

			$tokenInstance = $this->componentManager->getComponent($providerInstance->getTokenClassname());
			$this->tokens[] = $tokenInstance;

			if ($provider['patternType'] != '') {
				$requestPattern = $this->componentManager->getComponent($this->requestPatternResolver->resolveRequestPatternClass($provider['patternType']));
				$requestPattern->setPattern($provider['patternValue']);
				$tokenInstance->setRequestPattern($requestPattern);
			}
		}
	}
}

?>