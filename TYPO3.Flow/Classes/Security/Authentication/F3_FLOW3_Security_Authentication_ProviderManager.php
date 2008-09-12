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
 * @version $Id:$
 */

/**
 * The default authentication manager, which uses different F3::FLOW3::Security::Authentication::Providers
 * to authenticate the tokens stored in the security context.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ProviderManager implements F3::FLOW3::Security::Authentication::ManagerInterface {

	/**
	 * @var F3::FLOW3::Component::FactoryInterface The component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3::FLOW3::Security::Authentication::ProviderResolver The provider resolver
	 */
	protected $providerResolver;

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
	 * @param F3::FLOW3::Component::Factory $componentFactory The component factory
	 * @param F3::FLOW3::Security::Authentication::ProviderResolver $providerResolver The provider resolver
	 * @param F3::FLOW3::Security::RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Configuration::Manager $configurationManager,
			F3::FLOW3::Component::FactoryInterface $componentFactory,
			F3::FLOW3::Security::Authentication::ProviderResolver $providerResolver,
			F3::FLOW3::Security::RequestPatternResolver $requestPatternResolver) {

		$this->componentFactory = $componentFactory;
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
	 * Tries to authenticate the given token with the available authentication providers.
	 * If authentication fails and a F3::FLOW3::Security::Authentication::EntryPoint is set for the token, the entry point
	 * is called.
	 *
	 * @param F3::FLOW3::Security::Authentication::TokenInterface $authenticationToken The token to be authenticated
	 * @return F3::FLOW3::Security::Authentication::TokenInterface The authenticated token, NULL if authentication failed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate(F3::FLOW3::Security::Authentication::TokenInterface $authenticationToken) {
		foreach ($this->providers as $provider) {
			if ($provider->canAuthenticate($authenticationToken)) {
				$provider->authenticate($authenticationToken);
				break;
			}
		}
	}

	/**
	 * Builds the provider and token objects based on the given configuration
	 *
	 * @param F3::FLOW3::Configuration::Container $configuration The provider configuration
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo resolve and set authentication entry point and user details service in the tokens
	 */
	protected function buildProvidersAndTokensFromConfiguration(F3::FLOW3::Configuration::Container $configuration) {
		foreach ($configuration->security->authentication->providers as $provider) {
			$providerInstance = $this->componentFactory->getComponent($this->providerResolver->resolveProviderClass($provider['provider']));
			$this->providers[] = $providerInstance;

			$tokenInstance = $this->componentFactory->getComponent($providerInstance->getTokenClassname());
			$this->tokens[] = $tokenInstance;

			if ($provider['patternType'] != '') {
				$requestPattern = $this->componentFactory->getComponent($this->requestPatternResolver->resolveRequestPatternClass($provider['patternType']));
				$requestPattern->setPattern($provider['patternValue']);
				$tokenInstance->setRequestPattern($requestPattern);
			}
		}
	}
}

?>