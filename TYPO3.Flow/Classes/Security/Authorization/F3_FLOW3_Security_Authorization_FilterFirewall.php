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
 * Default Firewall which analyzes the request with a RequestFilter chain.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_FilterFirewall implements F3_FLOW3_Security_Authorization_FirewallInterface {

	/**
	 * @var F3_FLOW3_Component_Factory The component factory
	 */
	protected $componentFactory = NULL;

	/**
	 * @var F3_FLOW3_Security_RequestPatternResolver The request pattern resolver
	 */
	protected $requestPatternResolver = NULL;

	/**
	 * @var F3_FLOW3_Security_Authorization_InterceptorResolver The interceptor resolver
	 */
	protected $interceptorResolver = NULL;

	/**
	 * @var array Array of F3_FLOW3_Security_RequestFilter objects
	 */
	protected $filters = array();

	/**
	 * @var boolean If set to TRUE the firewall will reject any request except the ones explicitly whitelisted by a F3_FLOW3_Security_Authorization_AccessGrantInterceptor
	 */
	protected $rejectAll = FALSE;

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager The configuration manager
	 * @param F3_FLOW3_Component_Factory $componentFactory The component factory
	 * @param F3_FLOW3_Security_RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @param F3_FLOW3_Security_Authorization_InterceptorResolver $interceptorResolver The interceptor resolver
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Configuration_Manager $configurationManager,
			F3_FLOW3_Component_FactoryInterface $componentFactory,
			F3_FLOW3_Security_RequestPatternResolver $requestPatternResolver,
			F3_FLOW3_Security_Authorization_InterceptorResolver $interceptorResolver) {

		$this->componentFactory = $componentFactory;
		$this->requestPatternResolver = $requestPatternResolver;
		$this->interceptorResolver = $interceptorResolver;
		$configuration = $configurationManager->getSettings('FLOW3');

		$this->rejectAll = $configuration->security->firewall->rejectAll;
		$this->buildFiltersFromConfiguration($configuration->security->firewall->filters);
	}

	/**
	 * Returns the configure filters.
	 *
	 * @return array Array of configured filters.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * Analyzes a request against the configured firewall rules and blocks
	 * any illegal request.
	 *
	 * @param F3_FLOW3_MVC_Request $request The request to be analyzed
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function blockIllegalRequests(F3_FLOW3_MVC_Request $request) {
		$filterMatched = FALSE;

		foreach($this->filters as $filter) {
			if($filter->filterRequest($request)) $filterMatched = TRUE;
		}

		if($this->rejectAll && !$filterMatched) throw new F3_FLOW3_Security_Exception_AccessDenied('The requst was blocked, because no request filter explicitly allowed it.', 1216923741);
	}

	/**
	 * Sets the internal filters based on the given configuration.
	 *
	 * @param array $filterConfiguration The filter configuration
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildFiltersFromConfiguration($filterConfiguration) {
		foreach($filterConfiguration as $filter) {
			$requestPattern = $this->componentFactory->getComponent($this->requestPatternResolver->resolveRequestPatternClass($filter['patternType']));
			$requestPattern->setPattern($filter['patternValue']);
			$interceptor = $this->componentFactory->getComponent($this->interceptorResolver->resolveInterceptorClass($filter['interceptor']));

			$this->filters[] = $this->componentFactory->getComponent('F3_FLOW3_Security_Authorization_RequestFilter', $requestPattern, $interceptor);
		}
	}
}

?>