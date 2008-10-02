<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web::Routing;

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
 * @subpackage MVC
 * @version $Id$
 */

/**
 * dynamic route part
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class DynamicRoutePart extends F3::FLOW3::MVC::Web::Routing::AbstractRoutePart {

	/**
	 * @var string if not empty, match() will check existence of $splitString in current URL segment.
	 */
	protected $splitString;

	/**
	 * Sets split string.
	 *
	 * If not empty, match() will check the existence of $splitString in the current URL segment.
	 * If the URL segment does not contain $splitString, the route part won't match.
	 * Otherwise all characters before $splitString are removed from the URL segment.
	 *
	 * @param string $splitString
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setSplitString($splitString) {
		$this->splitString = $splitString;
	}

	/**
	 * Checks whether this dynamic route part corresponds to the given $urlSegments.
	 *
	 * On successful match this method sets $this->value to the corresponding urlPart
	 * and shortens $urlSegments respectively.
	 * If the first element of $urlSegments is empty, $this->value is set to $this->defaultValue
	 * (if it exists).
	 *
	 * @param array $urlSegments An array with one element per request URL segment.
	 * @return boolean TRUE if route part matched $urlSegments, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	final public function match(array &$urlSegments) {
		$this->value = NULL;

		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$valueToMatch = $this->findValueToMatch($urlSegments);
		if (!$this->matchValue($valueToMatch)) {
			return FALSE;
		}
		if (F3::PHP6::Functions::strlen($valueToMatch)) {
			$urlSegments[0] = F3::PHP6::Functions::substr($urlSegments[0], F3::PHP6::Functions::strlen($valueToMatch));
		}
		if (F3::PHP6::Functions::strlen($this->splitString) == 0 && isset($urlSegments[0]) && F3::PHP6::Functions::strlen($urlSegments[0]) == 0) {
			array_shift($urlSegments);
		}

		return TRUE;
	}

	/**
	 * Returns the first URL segment.
	 * If a split string is set, only the first part of the value is returned.
	 * 
	 * @param array $urlSegments
	 * @return string value to match, or an empty string if no URL segment is left or split string was not found
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function findValueToMatch(array $urlSegments) {
		if (!isset($urlSegments[0])) {
			return '';
		}
		$valueToMatch = $urlSegments[0];
		if (F3::PHP6::Functions::strlen($this->splitString) > 0) {
			$splitStringPosition = F3::PHP6::Functions::strpos($valueToMatch, $this->splitString);
			if ($splitStringPosition === FALSE) {
				return '';
			}
			$valueToMatch = F3::PHP6::Functions::substr($valueToMatch, 0, $splitStringPosition);
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
		if (!F3::PHP6::Functions::strlen($value)) {
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
	 * Checks whether $routeValues contains elements which correspond to this dynamic route part.
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
}
?>