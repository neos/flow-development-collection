<?php
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
 * A RequestFilter is configured to match specific \F3\FLOW3\MVC\RequestInterfaces and call
 * a \F3\FLOW3\Security\Authorization\InterceptorInterface if needed.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class RequestFilter {

	/**
	 * @var \F3\FLOW3\Security\RequestPatternInterface
	 */
	protected $pattern = NULL;

	/**
	 * @var \F3\FLOW3\Security\Authorization\InterceptorInterface
	 */
	protected $securityInterceptor = NULL;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Security\RequestPatternInterface $pattern The pattern this filter matches
	 * @param \F3\FLOW3\Security\Authorization\InterceptorInterface $securityInterceptor The interceptor called on pattern match
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Security\RequestPatternInterface $pattern, \F3\FLOW3\Security\Authorization\InterceptorInterface $securityInterceptor) {
		$this->pattern = $pattern;
		$this->securityInterceptor = $securityInterceptor;
	}

	/**
	 * Returns the set request pattern
	 *
	 * @return \F3\FLOW3\Security\RequestPatternInterface The set request pattern
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRequestPattern() {
		return $this->pattern;
	}

	/**
	 * Returns the set security interceptor
	 *
	 * @return \F3\FLOW3\Security\Authorization\InterceptorInterface The set security interceptor
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getSecurityInterceptor() {
		return $this->securityInterceptor;
	}

	/**
	 * Tries to match the given request against this filter and calls the set security interceptor on success.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request to be matched
	 * @return boolean Returns TRUE if the filter matched, FALSE otherwise
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		if($this->pattern->canMatch($request) && $this->pattern->matchRequest($request)) {
			$this->securityInterceptor->invoke();
			return TRUE;
		}
		return FALSE;
	}
}

?>