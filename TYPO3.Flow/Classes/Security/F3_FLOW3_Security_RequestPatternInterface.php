<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security;

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
 * Contract for a request pattern.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 */
interface RequestPatternInterface {

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param F3::FLOW3::MVC::Request $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canMatch(F3::FLOW3::MVC::Request $request);

	/**
	 * Returns the set pattern
	 *
	 * @return string The set pattern
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPattern();

	/**
	 * Sets the pattern (match) configuration
	 *
	 * @param object $pattern The pattern (match) configuration
	 * @return void
	 */
	public function setPattern($pattern);

	/**
	 * Matches a F3::FLOW3::MVC::Request against its set pattern rules
	 *
	 * @param F3::FLOW3::MVC::Request $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 */
	public function matchRequest(F3::FLOW3::MVC::Request $request);
}

?>