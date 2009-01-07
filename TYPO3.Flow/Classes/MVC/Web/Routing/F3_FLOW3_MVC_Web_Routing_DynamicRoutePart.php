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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Dynamic Route Part
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class DynamicRoutePart extends \F3\FLOW3\MVC\Web\Routing\AbstractRoutePart {

	/**
	 * Checks whether this Dynamic Route Part corresponds to the given $uriSegments.
	 *
	 * On successful match this method sets $this->value to the corresponding uriPart
	 * and shortens $uriSegments respectively.
	 * If the first element of $uriSegments is empty, $this->value is set to $this->defaultValue
	 * (if it exists).
	 *
	 * @param array $uriSegments An array with one element per request URI segment.
	 * @return boolean TRUE if Route Part matched $uriSegments, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	final public function match(array &$uriSegments) {
		$this->value = NULL;

		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$valueToMatch = $this->findValueToMatch($uriSegments);
		if (!$this->matchValue($valueToMatch)) {
			return FALSE;
		}
		if (\F3\PHP6\Functions::strlen($valueToMatch)) {
			$uriSegments[0] = \F3\PHP6\Functions::substr($uriSegments[0], \F3\PHP6\Functions::strlen($valueToMatch));
		}
		if (isset($uriSegments[0]) && \F3\PHP6\Functions::strlen($uriSegments[0]) == 0 && $this->getNextRoutePartInCurrentUriPatternSegment() === NULL) {
			array_shift($uriSegments);
		}

		return TRUE;
	}

	/**
	 * Returns the first URI segment.
	 * If a split string is set, only the first part of the value is returned.
	 *
	 * @param array $uriSegments
	 * @return string value to match, or an empty string if no URI segment is left or split string was not found
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function findValueToMatch(array $uriSegments) {
		if (!isset($uriSegments[0])) {
			return '';
		}
		$valueToMatch = $uriSegments[0];
		$splitString = $this->getSplitString();
		if (\F3\PHP6\Functions::strlen($splitString) > 0) {
			$splitStringPosition = \F3\PHP6\Functions::strpos($valueToMatch, $splitString);
			if ($splitStringPosition === FALSE) {
				return '';
			}
			$valueToMatch = \F3\PHP6\Functions::substr($valueToMatch, 0, $splitStringPosition);
		}
		return $valueToMatch;
	}

	/**
	 * Checks, whether given value can be matched.
	 * If $value is empty, this method checks whether a default value exists.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $value value to match
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function matchValue($value) {
		if (!\F3\PHP6\Functions::strlen($value)) {
			if (!isset($this->defaultValue)) {
				return FALSE;
			}
			$this->value = $this->defaultValue;
		} else {
			$this->value = $value;
		}
		return TRUE;
	}

	/**
	 * Checks whether $routeValues contains elements which correspond to this Dynamic Route Part.
	 * If a corresponding element is found in $routeValues, this element is removed from the array.
	 *
	 * @param array $routeValues
	 * @return boolean
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	final public function resolve(array &$routeValues) {
		$this->value = NULL;

		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$valueToResolve = isset($routeValues[$this->name]) ? $routeValues[$this->name] : NULL;
		if (!$this->resolveValue($valueToResolve)) {
			return FALSE;
		}
		unset($routeValues[$this->name]);

		return TRUE;
	}

	/**
	 * Checks, whether given value can be resolved.
	 * If $value is empty, this method checks whether a default value exists.
	 * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
	 *
	 * @param string $value value to resolve
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function resolveValue($value) {
		if (!isset($value)) {
			if (!isset($this->defaultValue)) {
				return FALSE;
			}
			$this->value = $this->defaultValue;
		} else {
			$this->value = $value;
		}
		return TRUE;
	}

	/**
	 * Returns the next Route Parts name. This will be used to locate the end of this Dynamic Route Part.
	 * The next Route Part must be NULL or an instance of tpye \F3\FLOW3\MVC\Web\Routing\StaticRoutePart
	 * because two Dynamic Route Parts can't directly follow each other.
	 * 
	 * @return string value of the following Route Part if it exists. Otherwise an empty string.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getSplitString() {
		$nextRoutePart = $this->getNextRoutePartInCurrentUriPatternSegment();
		if ($nextRoutePart === NULL) {
			return '';
		}
		return $nextRoutePart->getName();
	}
}
?>
