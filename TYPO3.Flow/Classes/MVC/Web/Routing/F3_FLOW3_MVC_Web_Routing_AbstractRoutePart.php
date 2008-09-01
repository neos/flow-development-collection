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
 * abstract route part
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class F3_FLOW3_MVC_Web_Routing_AbstractRoutePart {

	/**
	 * Name of the Route part
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Value of the Route part after decoding.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Default value of the Route part.
	 *
	 * @var mixed
	 */
	protected $defaultValue;

	/**
	 * Returns name of the Route part.
	 * 
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets name of the Route part.
	 * 
	 * @param string $name
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setName($partName) {
		$this->name = $partName;
	}

	/**
	 * Returns value of the Route part. Before match() is called this returns NULL.
	 * 
	 * @return mixed
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Sets default value of the Route part.
	 * 
	 * @param mixed $defaultValue
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
	}

	/**
	 * Checks whether this Route part corresponds to the given $urlSegments.
	 * This method does not only check if the Route part matches. It can also
	 * shorten the $urlSegments-Array by one or more elements when matching is successful.
	 * This is why $urlSegments has to be passed by reference.
	 *
	 * @param array $urlSegments An array with one element per request URL segment.
	 * @return boolean TRUE if route part matched $urlSegments, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public abstract function match(array &$urlSegments);

	/**
	 * Checks whether this Route part corresponds to the given $routeValues.
	 * This method does not only check if the Route part matches. It also
	 * removes resolved elements from $routeValues-Array.
	 * This is why $routeValues has to be passed by reference.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by dynamic route parts.
	 * @return boolean TRUE if route part can resolve one or more $routeValues elements, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public abstract function resolve(array &$routeValues);

}
?>