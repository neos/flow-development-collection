<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization;

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
 * Default Firewall which analyzes the request with a RequestFilter chain.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FilterFirewall implements \F3\FLOW3\Security\Authorization\FirewallInterface {

	/**
	 * @var \F3\FLOW3\Object\ObjectManager The object manager
	 */
	protected $objectManager = NULL;

	/**
	 * @var \F3\FLOW3\Security\RequestPatternResolver The request pattern resolver
	 */
	protected $requestPatternResolver = NULL;

	/**
	 * @var \F3\FLOW3\Security\Authorization\InterceptorResolver The interceptor resolver
	 */
	protected $interceptorResolver = NULL;

	/**
	 * @var array Array of \F3\FLOW3\Security\RequestFilter objects
	 */
	protected $filters = array();

	/**
	 * @var boolean If set to TRUE the firewall will reject any request except the ones explicitly whitelisted by a \F3\FLOW3\Security\Authorization\AccessGrantInterceptor
	 */
	protected $rejectAll = FALSE;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager The object manager
	 * @param \F3\FLOW3\Security\RequestPatternResolver $requestPatternResolver The request pattern resolver
	 * @param \F3\FLOW3\Security\Authorization\InterceptorResolver $interceptorResolver The interceptor resolver
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Object\ObjectManagerInterface $objectManager,
			\F3\FLOW3\Security\RequestPatternResolver $requestPatternResolver,
			\F3\FLOW3\Security\Authorization\InterceptorResolver $interceptorResolver) {

		$this->objectManager = $objectManager;
		$this->requestPatternResolver = $requestPatternResolver;
		$this->interceptorResolver = $interceptorResolver;
	}

	/**
	 * Injects the configuration settings
	 *
	 * @param array $settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings(array $settings) {
		$this->rejectAll = $settings['security']['firewall']['rejectAll'];
		$this->buildFiltersFromSettings($settings['security']['firewall']['filters']);
	}

	/**
	 * Analyzes a request against the configured firewall rules and blocks
	 * any illegal request.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request to be analyzed
	 * @return void
	 * @throws \F3\FLOW3\Security\Exception\AccessDeniedException if the
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function blockIllegalRequests(\F3\FLOW3\MVC\RequestInterface $request) {
		$filterMatched = FALSE;
		foreach($this->filters as $filter) {
			if($filter->filterRequest($request)) $filterMatched = TRUE;
		}
		if ($this->rejectAll && !$filterMatched) throw new \F3\FLOW3\Security\Exception\AccessDeniedException('The requst was blocked, because no request filter explicitly allowed it.', 1216923741);
	}

	/**
	 * Sets the internal filters based on the given configuration.
	 *
	 * @param array $filterSettings The filter settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildFiltersFromSettings(array $filterSettings) {
		foreach($filterSettings as $singleFilterSettings) {
			$requestPattern = $this->objectManager->getObject($this->requestPatternResolver->resolveRequestPatternClass($singleFilterSettings['patternType']));
			$requestPattern->setPattern($singleFilterSettings['patternValue']);
			$interceptor = $this->objectManager->getObject($this->interceptorResolver->resolveInterceptorClass($singleFilterSettings['interceptor']));

			$this->filters[] = $this->objectManager->getObject('F3\FLOW3\Security\Authorization\RequestFilter', $requestPattern, $interceptor);
		}
	}
}

?>