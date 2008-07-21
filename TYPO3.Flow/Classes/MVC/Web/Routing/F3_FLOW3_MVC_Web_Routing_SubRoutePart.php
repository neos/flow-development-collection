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
 * sub route part
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Web_Routing_SubRoutePart extends F3_FLOW3_MVC_Web_Routing_AbstractRoutePart {

	/**
	 * Checks whether this SubRoute part correspond to the given $urlSegments.
	 * This method iterates through $urlSegments elements and sets $this->value to an array
	 * by taking odd elements as keys and even elements as values.
	 * So "param1/param2/param3/param4" gets array('param1' => 'param2', 'param3' => 'param4').
	 * If successful, $urlSegments is empty at the end of this method.
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

		if (empty($this->defaultValue)) {
			if (count($urlSegments) < 1 || empty($urlSegments[0])) {
				return FALSE;
			}
		}

		if (!isset($urlSegments[0]) || empty($urlSegments[0])) {
			$this->value = $this->defaultValue;
			return TRUE;
		}

		$this->value = array();
		for ($i = 0; $i < count($urlSegments); $i += 2) {
			$this->value[$urlSegments[$i]] = isset($urlSegments[$i+1]) ? $urlSegments[$i+1] : NULL;
		}
		$urlSegments = array();

		return TRUE;
	}

}
?>