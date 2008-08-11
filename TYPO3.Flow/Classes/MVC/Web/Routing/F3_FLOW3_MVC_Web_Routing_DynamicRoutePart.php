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
class F3_FLOW3_MVC_Web_Routing_DynamicRoutePart extends F3_FLOW3_MVC_Web_Routing_AbstractRoutePart {

	/**
	 * @var string if not empty, match() will check existence of $splitString in current URL segment.
	 */
	protected $splitString;

	/**
	 * Sets split string.
	 * if not empty, match() will check existence of $splitString in current URL segment.
	 * If URL segment does not contain $splitString, route part won't match.
	 * Otherwise all characters before $splitString are removed from URL segment.
	 *
	 * @param string $splitString
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setSplitString($splitString) {
		$this->splitString = $splitString;
	}

	/**
	 * Checks whether this dynamic Route part correspond to the given $urlSegments.
	 * On successful match this method sets $this->value to the corresponding urlPart
	 * and shortens $urlSegments respectively.
	 * If first element of $urlSegments is empty, $this->value is set to $this->defaultValue (if existent).
	 *
	 * @param array $urlSegments An array with one element per request URL segment.
	 * @return boolean TRUE if route part matched $urlSegments, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function match(array &$urlSegments) {
		$this->value = NULL;

		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}

		$valueToMatch = isset($urlSegments[0]) ? $urlSegments[0] : NULL;
		if (F3_PHP6_Functions::strlen($this->splitString) > 0 && F3_PHP6_Functions::strlen($valueToMatch)) {
			$splitStringPosition = F3_PHP6_Functions::strpos($valueToMatch, $this->splitString);
			if ($splitStringPosition === FALSE) {
				return FALSE;
			}
			$valueToMatch = F3_PHP6_Functions::substr($valueToMatch, 0, $splitStringPosition);
		}

		if (!F3_PHP6_Functions::strlen($valueToMatch)) {
			if (empty($this->defaultValue)) {
				return FALSE;
			}
			$this->value = $this->defaultValue;
			return TRUE;
		}

		$this->value = $valueToMatch;
		$urlSegments[0] = F3_PHP6_Functions::substr($urlSegments[0], F3_PHP6_Functions::strlen($valueToMatch));
		if (F3_PHP6_Functions::strlen($urlSegments[0]) == 0) {
			array_shift($urlSegments);
		}

		return TRUE;
	}
}
?>