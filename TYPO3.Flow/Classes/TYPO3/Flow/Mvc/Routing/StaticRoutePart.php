<?php
namespace TYPO3\Flow\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Static Route Part
 *
 */
class StaticRoutePart extends \TYPO3\Flow\Mvc\Routing\AbstractRoutePart {

	/**
	 * Gets default value of the Route Part.
	 *
	 * @return string
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
	 */
	public function match(&$routePath) {
		$this->value = NULL;
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
	 * @param array $routeValues not used but needed to implement \TYPO3\Flow\Mvc\Routing\AbstractRoutePart
	 * @return boolean
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