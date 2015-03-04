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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Arrays;

/**
 * Dynamic Route Part
 *
 * @api
 */
class DynamicRoutePart extends AbstractRoutePart implements DynamicRoutePartInterface {

	/**
	 * @var PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * The split string represents the end of a Dynamic Route Part.
	 * If it is empty, Route Part will be equal to the remaining request path.
	 *
	 * @var string
	 */
	protected $splitString = '';

	/**
	 * Sets split string of the Route Part.
	 *
	 * @param string $splitString
	 * @return void
	 * @api
	 */
	public function setSplitString($splitString) {
		$this->splitString = $splitString;
	}

	/**
	 * Checks whether this Dynamic Route Part corresponds to the given $routePath.
	 *
	 * On successful match this method sets $this->value to the corresponding uriPart
	 * and shortens $routePath respectively.
	 *
	 * @param string $routePath The request path to be matched - without query parameters, host and fragment.
	 * @return boolean TRUE if Route Part matched $routePath, otherwise FALSE.
	 */
	final public function match(&$routePath) {
		$this->value = NULL;
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$valueToMatch = $this->findValueToMatch($routePath);
		$matchResult = $this->matchValue($valueToMatch);
		if ($matchResult !== TRUE) {
			return $matchResult;
		}
		$this->removeMatchingPortionFromRequestPath($routePath, $valueToMatch);

		return TRUE;
	}

	/**
	 * Returns the first part of $routePath.
	 * If a split string is set, only the first part of the value until location of the splitString is returned.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $routePath The request path to be matched
	 * @return string value to match, or an empty string if $routePath is empty or split string was not found
	 * @api
	 */
	protected function findValueToMatch($routePath) {
		if (!isset($routePath) || $routePath === '' || $routePath[0] === '/') {
			return '';
		}
		$valueToMatch = $routePath;
		if ($this->splitString !== '') {
			$splitStringPosition = strpos($valueToMatch, $this->splitString);
			if ($splitStringPosition !== FALSE) {
				$valueToMatch = substr($valueToMatch, 0, $splitStringPosition);
			}
		}
		if (strpos($valueToMatch, '/') !== FALSE) {
			return '';
		}
		return $valueToMatch;
	}

	/**
	 * Checks, whether given value can be matched.
	 * In the case of default Dynamic Route Parts a value matches when it's not empty.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $value value to match
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 * @api
	 */
	protected function matchValue($value) {
		if ($value === NULL || $value === '') {
			return FALSE;
		}
		$this->value = urldecode($value);
		return TRUE;
	}

	/**
	 * Removes matching part from $routePath.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $routePath The request path to be matched
	 * @param string $valueToMatch The matching value
	 * @return void
	 * @api
	 */
	protected function removeMatchingPortionFromRequestPath(&$routePath, $valueToMatch) {
		if ($valueToMatch !== NULL && $valueToMatch !== '') {
			$routePath = substr($routePath, strlen($valueToMatch));
		}
	}

	/**
	 * Checks whether $routeValues contains elements which correspond to this Dynamic Route Part.
	 * If a corresponding element is found in $routeValues, this element is removed from the array.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return boolean TRUE if current Route Part could be resolved, otherwise FALSE
	 */
	final public function resolve(array &$routeValues) {
		$this->value = NULL;
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$valueToResolve = $this->findValueToResolve($routeValues);
		if (!$this->resolveValue($valueToResolve)) {
			return FALSE;
		}
		$routeValues = Arrays::unsetValueByPath($routeValues, $this->name);
		return TRUE;
	}

	/**
	 * Returns the route value of the current route part.
	 * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return string|array value to resolve.
	 * @api
	 */
	protected function findValueToResolve(array $routeValues) {
		return ObjectAccess::getPropertyPath($routeValues, $this->name);
	}

	/**
	 * Checks, whether given value can be resolved and if so, sets $this->value to the resolved value.
	 * If $value is empty, this method checks whether a default value exists.
	 * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
	 *
	 * @param mixed $value value to resolve
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 * @api
	 */
	protected function resolveValue($value) {
		if ($value === NULL) {
			return FALSE;
		}
		if (is_object($value)) {
			$value = $this->persistenceManager->getIdentifierByObject($value);
			if ($value === NULL || (!is_string($value) && !is_integer($value))) {
				return FALSE;
			}
		}
		$this->value = urlencode($value);
		if ($this->lowerCase) {
			$this->value = strtolower($this->value);
		}
		return TRUE;
	}
}
