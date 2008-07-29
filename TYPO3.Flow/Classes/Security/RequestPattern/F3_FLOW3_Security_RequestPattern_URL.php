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
 * This class holds an URL pattern an decides, if a F3_FLOW3_MVC_Web_Request object matches against this pattern
 * Note: This pattern can only be used for web requests.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Security_RequestPattern_URL implements F3_FLOW3_Security_RequestPatternInterface {

	/**
	 * @var string The preg_match() styled URL pattern
	 */
	protected $URLPattern = '';

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param F3_FLOW3_MVC_Request $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canMatch(F3_FLOW3_MVC_Request $request) {
		if($request instanceof F3_FLOW3_MVC_Web_Request) return TRUE;
		return FALSE;
	}

	/**
	 * Returns the set pattern
	 *
	 * @return string The set pattern
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPattern() {
		return $this->URLPattern;
	}

	/**
	 * Sets an URL pattern (preg_match() syntax)
	 *
	 * @param string $URLpattern The preg_match() styled URL pattern
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPattern($URLpattern) {
		$this->URLPattern = $URLpattern;
	}

	/**
	 * Matches a F3_FLOW3_MVC_Request against its set URL pattern rules
	 *
	 * @param F3_FLOW3_MVC_Request $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws F3_FLOW3_Security_Exception_RequestTypeNotSupported
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequest(F3_FLOW3_MVC_Request $request) {
		if(!($request instanceof F3_FLOW3_MVC_Web_Request)) throw new F3_FLOW3_Security_Exception_RequestTypeNotSupported('The given request type is not supported.', 1216903641);

		return (boolean)preg_match('/^' . str_replace('/', '\/', $this->URLPattern) . '$/', $request->getRequestURI()->getPath());
	}
}

?>