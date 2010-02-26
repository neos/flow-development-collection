<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * Static Route Part
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class StaticRoutePart extends \F3\FLOW3\MVC\Web\Routing\AbstractRoutePart {

	/**
	 * Gets default value of the Route Part.
	 *
	 * @return string $defaultValue
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getDefaultValue() {
		return $this->name;
	}

	/**
	 * Checks whether this Static Route Part correspond to the given $routePath.
	 * This is TRUE if $routePath is not empty and the first part is equal to the Route Part name.
	 *
	 * @param string $routePath The request path to be matched - without query parameters, host and fragment.
	 * @return boolean TRUE if Route Part matched $routePath, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function match(&$routePath) {
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		if ($routePath === '') {
			return FALSE;
		}
		$valueToMatch = substr($routePath, 0, strlen($this->name));
		if ($valueToMatch !== $this->name) {
			return FALSE;
		}
		$shortenedRequestPath = substr($routePath, strlen($valueToMatch));
		$routePath = ($shortenedRequestPath !== FALSE) ? $shortenedRequestPath : '';

		return TRUE;
	}

	/**
	 * Sets the Route Part value to the Route Part name and returns TRUE if successful.
	 *
	 * @param array $routeValues not used but needed to implement \F3\FLOW3\MVC\Web\Routing\AbstractRoutePart
	 * @return boolean
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function resolve(array &$routeValues) {
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$this->value = $this->name;
		if ($this->lowerCase) {
			$this->value = strtolower($this->value);
		}
		return TRUE;
	}
}

?>
