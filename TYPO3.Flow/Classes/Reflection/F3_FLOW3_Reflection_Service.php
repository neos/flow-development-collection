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
 * @subpackage Reflection
 * @version $Id:$
 */

/**
 * A service for aquiring reflection based information in a performant way.
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Reflection_Service {

	/**
	 * All available class names to consider
	 *
	 * @var array
	 */
	protected $availableClassNames = array();

	/**
	 * Names of interfaces and an array of class names implementing these
	 *
	 * @var array
	 */
	protected $interfaceImplementations = array();

	/**
	 * Initializes this service
	 *
	 * @param array $availableClassNames Names of available classes to consider in this reflection service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize(array $availableClassNames) {
		$this->availableClassNames = $availableClassNames;

		foreach ($this->availableClassNames as $className) {
			$class = new ReflectionClass($className);
			foreach ($class->getInterfaces() as $interface) {
				if (!$class->isAbstract()) {
					$this->interfaceImplementations[$interface->getName()][] = $className;
				}
			}
		}
	}

	/**
	 * Searches for and returns the class name of the default implementation of the given
	 * interface name. If no class implementing the interface was found or more than one
	 * implementation was found in the package defining the interface, FALSE is returned.
	 *
	 * @param string $interfaceName Name of the interface
	 * @return mixed Either the class name of the default implementation for the component type or FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_Component_Exception_UnknownInterface if the specified interface does not exist.
	 */
	public function getDefaultImplementationClassNameForInterface($interfaceName) {
		$classNamesFound = key_exists($interfaceName, $this->interfaceImplementations) ? $this->interfaceImplementations[$interfaceName] : array();
		return (count($classNamesFound) == 1 ? $classNamesFound[0] : FALSE);
	}

	/**
	 * Searches for and returns all class names of implementations of the given component type
	 * (interface name). If no class implementing the interface was found, FALSE is returned.
	 *
	 * @param string $interfaceName Name of the interface
	 * @return array An array of class names of the default implementation for the component type
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_Component_Exception_UnknownInterface if the given interface does not exist
	 */
	public function getAllImplementationClassNamesForInterface($interfaceName) {
		return key_exists($interfaceName, $this->interfaceImplementations) ? $this->interfaceImplementations[$interfaceName] : array();
	}



}
?>