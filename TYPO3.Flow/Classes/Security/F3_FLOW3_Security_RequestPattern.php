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
 * This class holds a pattern an decides, if a F3_FLOW3_MVC_Request object matches against this pattern
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_RequestPattern {

//TODO: This can also be set by configuration
	/**
	 * @var string The preg_match() styled URL pattern
	 */
	protected $URLPattern = '';

	/**
	 * Sets an URL pattern (preg_match() syntax)
	 *
	 * @param string $pattern The preg_match() styled URL pattern
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setURLPattern($pattern) {

	}

	/**
	 * Matches a F3_FLOW3_MVC_Request against its set pattern rules
	 *
	 * @param F3_FLOW3_MVC_Request $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequest(F3_FLOW3_MVC_Request $request) {

	}
}

?>