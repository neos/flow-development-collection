<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\RequestPattern;

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
 * @package FLOW3
 * @subpackage Security
 * @version $Id: URL.php 1811 2009-01-28 12:04:49Z robert $
 */

/**
 * This class holds an controller object name pattern an decides, if a \F3\FLOW3\MVC\Web\Request object matches against this pattern
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id: URL.php 1811 2009-01-28 12:04:49Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ControllerObjectName implements \F3\FLOW3\Security\RequestPatternInterface {

	/**
	 * @var string The preg_match() styled controller object name pattern
	 */
	protected $controllerObjectNamePattern = '';

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param \F3\FLOW3\MVC\Request $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canMatch(\F3\FLOW3\MVC\Request $request) {
		return TRUE;
	}

	/**
	 * Returns the set pattern
	 *
	 * @return string The set pattern
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPattern() {
		return $this->controllerObjectNamePattern;
	}

	/**
	 * Sets an controller object name pattern (preg_match() syntax)
	 *
	 * @param string $controllerObjectNamePattern The preg_match() styled controller object name pattern
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPattern($controllerObjectNamePattern) {
		$this->controllerObjectNamePattern = $controllerObjectNamePattern;
	}

	/**
	 * Matches a \F3\FLOW3\MVC\Request against its set controller object name pattern rules
	 *
	 * @param \F3\FLOW3\MVC\Request $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws \F3\FLOW3\Security\Exception\RequestTypeNotSupported
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequest(\F3\FLOW3\MVC\Request $request) {
		return (boolean)preg_match('/^' . str_replace('\\', '\\\\', $this->controllerObjectNamePattern) . '$/', $request->getControllerObjectName());
	}
}

?>