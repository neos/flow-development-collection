<?php
namespace TYPO3\FLOW3\Mvc\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for all Route Parts.
 *
 * !!! Warning: If you write your own RoutePart handler which does some queries to the
 * persistence layer, be aware that *permission checks* are not yet done, i.e. you
 * get back *all* objects, not just the objects visible to the current role.
 */
interface RoutePartInterface {

	/**
	 * Sets name of the Route Part.
	 *
	 * @param string $partName
	 * @return void
	 */
	public function setName($partName);

	/**
	 * Returns name of the Route Part.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns TRUE if a value is set for this Route Part, otherwise FALSE.
	 *
	 * @return boolean
	 */
	public function hasValue();

	/**
	 * Returns value of the Route Part. Before match() is called this returns NULL.
	 *
	 * @return mixed
	 */
	public function getValue();

	/**
	 * Returns TRUE if a default value is set for this Route Part, otherwise FALSE.
	 *
	 * @return boolean
	 */
	public function hasDefaultValue();

	/**
	 * Sets default value of the Route Part.
	 *
	 * @param mixed $defaultValue
	 * @return void
	 */
	public function setDefaultValue($defaultValue);

	/**
	 * Gets default value of the Route Part.
	 *
	 * @return mixed $defaultValue
	 */
	public function getDefaultValue();

	/**
	 * Specifies whether this Route part is optional.
	 *
	 * @param boolean $isOptional TRUE: this Route part is optional. FALSE: this Route part is required.
	 * @return void
	 */
	public function setOptional($isOptional);

	/**
	 * @return boolean TRUE if this Route part is optional, otherwise FALSE.
	 * @see setOptional()
	 */
	public function isOptional();

 	/**
	 * Specifies whether this Route part should be converted to lower case when resolved.
	 *
	 * @param boolean $lowerCase TRUE: this Route part is converted to lower case. FALSE: this Route part is not altered.
	 * @return void
	 */
	public function setLowerCase($lowerCase);

	/**
	 * Getter for $this->lowerCase.
	 *
	 * @return boolean TRUE if this Route part will be converted to lower case, otherwise FALSE.
	 * @see setLowerCase()
	 */
	public function isLowerCase();

	/**
	 * Defines options for this Route Part.
	 * Options can be used to enrich a route part with parameters or settings like case sensivitity.
	 *
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options);

	/**
	 * @return array options of this Route Part.
	 */
	public function getOptions();

	/**
	 * Checks whether this Route Part corresponds to the given $routePath.
	 * This method does not only check if the Route Part matches. It can also
	 * shorten the $routePath by the matching substring when matching is successful.
	 * This is why $routePath has to be passed by reference.
	 *
	 * @param string &$routePath The request path to be matched - without query parameters, host and fragment.
	 * @return boolean TRUE if Route Part matched $routePath, otherwise FALSE.
	 */
	public function match(&$routePath);

	/**
	 * Checks whether this Route Part corresponds to the given $routeValues.
	 * This method does not only check if the Route Part matches. It also
	 * removes resolved elements from $routeValues-Array.
	 * This is why $routeValues has to be passed by reference.
	 *
	 * @param array &$routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return boolean TRUE if Route Part can resolve one or more $routeValues elements, otherwise FALSE.
	 */
	public function resolve(array &$routeValues);
}
?>