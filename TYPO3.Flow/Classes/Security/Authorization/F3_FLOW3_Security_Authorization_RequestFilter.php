<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization;

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
 * A RequestFilter is configured to match specific \F3\FLOW3\MVC\Requests and call
 * a \F3\FLOW3\Security\Authorization\InterceptorInterface if needed.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class RequestFilter {

	/**
	 * @var \F3\FLOW3\Security\RequestPatternInterface The request pattern this filter should match
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
	 * @param \F3\FLOW3\MVC\Request $request The request to be matched
	 * @return boolean Returns TRUE if the filter matched, FALSE otherwise
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterRequest(\F3\FLOW3\MVC\Request $request) {
		if($this->pattern->canMatch($request) && $this->pattern->matchRequest($request)) {
			$this->securityInterceptor->invoke();
			return TRUE;
		}
		return FALSE;
	}
}

?>