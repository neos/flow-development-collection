<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization;

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
 * Default Firewall which analyzes the request with a RequestFilter chain.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FilterFirewall implements F3::FLOW3::Security::Authorization::FirewallInterface {

	/**
	 * @var F3::FLOW3::Object::Manager The object manager
	 */
	protected $objectManager = NULL;

	/**
	 * @var F3::FLOW3::Security::RequestPatternResolver The request pattern resolver
	 */
	protected $requestPatternResolver = NULL;

	/**
	 * @var F3::FLOW3::Security::Authorization::InterceptorResolver The interceptor resolver
	 */
	protected $interceptorResolver = NULL;

	/**
	 * @var array Array of F3::FLOW3::Security::RequestFilter objects
	 */
	protected $filters = array();

	/**
	 * @var boolean If set to TRUE the firewall will reject any request except the ones explicitly whitelisted by a F3::FLOW3::Security::Authorization::AccessGrantInterceptor
	 */
	protected $rejectAll = FALSE;

	/**
	 * Constructor.
	 *
	 * @param F3::FLOW3::Configuration::Manager $configurationManager The configuration manager
	 * @param F3::FLOW3::Object::Manager $objectManager The object manager
	 * @param F3::FLOW3::Security::RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @param F3::FLOW3::Security::Authorization::InterceptorResolver $interceptorResolver The interceptor resolver
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Configuration::Manager $configurationManager,
			F3::FLOW3::Object::ManagerInterface $objectManager,
			F3::FLOW3::Security::RequestPatternResolver $requestPatternResolver,
			F3::FLOW3::Security::Authorization::InterceptorResolver $interceptorResolver) {

		$this->objectManager = $objectManager;
		$this->requestPatternResolver = $requestPatternResolver;
		$this->interceptorResolver = $interceptorResolver;
		$settings = $configurationManager->getSettings('FLOW3');

		$this->rejectAll = $settings['security']['firewall']['rejectAll'];
		$this->buildFiltersFromSettings($settings['security']['firewall']['filters']);
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
	 * @param F3::FLOW3::MVC::Request $request The request to be analyzed
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function blockIllegalRequests(F3::FLOW3::MVC::Request $request) {
		$filterMatched = FALSE;

		foreach($this->filters as $filter) {
			if($filter->filterRequest($request)) $filterMatched = TRUE;
		}

		if($this->rejectAll && !$filterMatched) throw new F3::FLOW3::Security::Exception::AccessDenied('The requst was blocked, because no request filter explicitly allowed it.', 1216923741);
	}

	/**
	 * Sets the internal filters based on the given configuration.
	 *
	 * @param array $filterConfiguration The filter configuration
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildFiltersFromSettings($filterSettings) {
		foreach($filterSettings as $singleFilterSettings) {
			$requestPattern = $this->objectManager->getObject($this->requestPatternResolver->resolveRequestPatternClass($singleFilterSettings['patternType']));
			$requestPattern->setPattern($singleFilterSettings['patternValue']);
			$interceptor = $this->objectManager->getObject($this->interceptorResolver->resolveInterceptorClass($singleFilterSettings['interceptor']));

			$this->filters[] = $this->objectManager->getObject('F3::FLOW3::Security::Authorization::RequestFilter', $requestPattern, $interceptor);
		}
	}
}

?>