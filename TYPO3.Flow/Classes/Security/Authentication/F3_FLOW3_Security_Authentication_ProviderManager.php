<?php

declare(ENCODING = 'utf-8');

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
 * The default authentication manager, which uses different F3_FLOW3_Security_Authentication_Providers
 * to authenticate the tokens stored in the security context.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authentication_ProviderManager implements F3_FLOW3_Security_Authentication_ManagerInterface {

	/**
	 * @var F3_FLOW3_Component_FactoryInterface The component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Security_Authentication_ProviderResolver The provider resolver
	 */
	protected $providerResolver;

	/**
	 * @var F3_FLOW3_Security_RequestPatternResolver The request pattern resolver
	 */
	protected $requestPatternResolver;

	/**
	 * @var array Array of F3_FLOW3_Security_Authentication_ProviderInterface objects
	 */
	protected $providers = array();

	/**
	 * @var array Array of F3_FLOW3_Security_Authentication_TokenInterface objects
	 */
	protected $tokens = array();

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager The configuration manager
	 * @param F3_FLOW3_Component_Factory $componentFactory The component factory
	 * @param F3_FLOW3_Security_Authentication_ProviderResolver $providerResolver The provider resolver
	 * @param F3_FLOW3_Security_RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Configuration_Manager $configurationManager,
								F3_FLOW3_Component_FactoryInterface $componentFactory,
								F3_FLOW3_Security_Authentication_ProviderResolver $providerResolver,
								F3_FLOW3_Security_RequestPatternResolver $requestPatternResolver) {

		$this->componentFactory = $componentFactory;
		$this->providerResolver = $providerResolver;
		$this->requestPatternResolver = $requestPatternResolver;

		$this->buildProvidersAndTokensFromConfiguration($configurationManager->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3));
	}

	/**
	 * Sets the providers
	 *
	 * @param array Array of providers (F3_FLOW3_Security_Authentication_ProviderInterface)
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
	 * @return array Array of F3_FLOW3_Security_Authentication_TokenInterface An array of tokens this manager is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getTokens() {
		return $this->tokens;
	}

	/**
	 * Tries to authenticate the given token with the available authentication providers.
	 * If authentication fails and a F3_FLOW3_Security_Authentication_EntryPoint is set for the token, the entry point
	 * is called.
	 *
	 * @param F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken The token to be authenticated
	 * @return F3_FLOW3_Security_Authentication_TokenInterface The authenticated token, NULL if authentication failed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate(F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken) {
		foreach($this->providers as $provider) {
			if($provider->canAuthenticate($authenticationToken)) {
				$provider->authenticate($authenticationToken);
				break;
			}
		}
	}

	/**
	 * Builds the provider and token objects based on the given configuration
	 *
	 * @param F3_FLOW3_Configuration_Container $configuration The provider configuration
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo resolve and set authentication entry point and user details service in the tokens
	 */
	protected function buildProvidersAndTokensFromConfiguration(F3_FLOW3_Configuration_Container $configuration) {
		foreach($configuration->security->authentication->providers as $provider) {
			$providerInstance = $this->componentFactory->getComponent($this->providerResolver->resolveProviderClass($provider['provider']));
			$this->providers[] = $providerInstance;

			$tokenInstance = $this->componentFactory->getComponent($providerInstance->getTokenClassname());
			$this->tokens[] = $tokenInstance;

			if($provider['patternType'] != '') {
				$requestPattern = $this->componentFactory->getComponent($this->requestPatternResolver->resolveRequestPatternClass($provider['patternType']));
				$requestPattern->setPattern($provider['patternValue']);
				$tokenInstance->setRequestPattern($requestPattern);
			}
		}
	}
}

?>