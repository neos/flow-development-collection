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
 * Contract for all Route Parts
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
interface RoutePartInterface {

	/**
	 * Sets name of the Route Part.
	 * 
	 * @param string $name
	 * @return void
	 * @internal
	 */
	public function setName($partName);

	/**
	 * Returns name of the Route Part.
	 * 
	 * @return string
	 * @internal
	 */
	public function getName();

	/**
	 * Returns TRUE if a value is set for this Route Part, otherwise FALSE.
	 * 
	 * @return boolean
	 * @internal
	 */
	public function hasValue();

	/**
	 * Returns value of the Route Part. Before match() is called this returns NULL.
	 * 
	 * @return mixed
	 * @internal
	 */
	public function getValue();

	/**
	 * Returns TRUE if a default value is set for this Route Part, otherwise FALSE.
	 * 
	 * @return boolean
	 * @internal
	 */
	public function hasDefaultValue();

	/**
	 * Sets default value of the Route Part.
	 * 
	 * @param mixed $defaultValue
	 * @return void
	 * @internal
	 */
	public function setDefaultValue($defaultValue);

	/**
	 * Gets default value of the Route Part.
	 * 
	 * @return mixed $defaultValue
	 * @internal
	 */
	public function getDefaultValue();

	/**
	 * Specifies whether this Route part is optional.
	 * 
	 * @param boolean $isOptional TRUE: this Route part is optional. FALSE: this Route part is required.
	 * @return void
	 * @internal
	 */
	public function setOptional($isOptional);

	/**
	 * @return boolean TRUE if this Route part is optional, otherwise FALSE.
	 * @see setOptional()
	 * @internal
	 */
	public function isOptional();

	/**
	 * Defines options for this Route Part.
	 * Options can be used to enrich a route part with parameters or settings like case sensivitity.
	 * 
	 * @param array $options
	 * @return void
	 * @internal
	 */
	public function setOptions(array $options);

	/**
	 * @return array options of this Route Part.
	 * @internal
	 */
	public function getOptions();

	/**
	 * Checks whether this Route Part corresponds to the given $requestPath.
	 * This method does not only check if the Route Part matches. It can also
	 * shorten the $requestPath by the matching substring when matching is successful.
	 * This is why $requestPath has to be passed by reference.
	 *
	 * @param string $requestPath The request path to be matched - without query parameters, host and fragment.
	 * @return boolean TRUE if Route Part matched $requestPath, otherwise FALSE.
	 * @internal
	 */
	public function match(&$requestPath);

	/**
	 * Checks whether this Route Part corresponds to the given $routeValues.
	 * This method does not only check if the Route Part matches. It also
	 * removes resolved elements from $routeValues-Array.
	 * This is why $routeValues has to be passed by reference.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return boolean TRUE if Route Part can resolve one or more $routeValues elements, otherwise FALSE.
	 * @internal
	 */
	public function resolve(array &$routeValues);
}
?>