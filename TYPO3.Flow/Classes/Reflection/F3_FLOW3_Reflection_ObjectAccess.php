<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

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
 * [Enter description here]
 *
 * @package
 * @subpackage
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ObjectAccess {
	
	const ACCESS_GET = 0;
	const ACCESS_SET = 1;
	const ACCESS_PUBLIC = 2;
	
	/**
	 * Get a property of a given object.
	 * Tries to get the property by the following ways:
	 * - if public getter method exists, call it.
	 * - if public property exists, return the value of it.
	 * - else, throw exception
	 * 
	 * @param object $object: Object to get the property from
	 * @param string $property: Property to retrieve
	 * @return object Value of the property.
	 * @throws F3\FLOW3\Reflection\Exception if property was not found or was no string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function getProperty($object, $property) {
		if (!is_string($property)) {
			throw new \F3\FLOW3\Reflection\Exception('Given property is not of type string.', 1231178303); //'
		}
		
		if (self::isPropertyAccessible($object, $property, self::ACCESS_GET)) {
			$getterMethodName = self::buildGetterMethodName($property);
			return call_user_func(array($object, $getterMethodName));
		} elseif (self::isPropertyAccessible($object, $property, self::ACCESS_PUBLIC)) {
			return $object->$property;
		} else {
			throw new \F3\FLOW3\Reflection\Exception('The property "' . $property . '" on class "' . get_class($object) . '" is not read accessible.', 1231176209); //'
		}
	}
	
	/**
	 * Set a property for a given object.
	 * Tries to set the property by the following ways:
	 * - if public setter method exists, call it.
	 * - if public property exists, set it directly.
	 * - else, throw exception
	 * 
	 * @param object $object: Object to get the property from
	 * @param string $property: Property to retrieve
	 * @param object $propertyValue Value of the property which should be set.
	 * @return void
	 * @throws F3\FLOW3\Reflection\Exception if property was not found or was no string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function setProperty($object, $property, $propertyValue) {
		if (!is_string($property)) {
			throw new \F3\FLOW3\Reflection\Exception('Given property is not of type string.', 1231178878); //'
		}
		
		if (self::isPropertyAccessible($object, $property, self::ACCESS_SET)) {
			$setterMethodName = self::buildSetterMethodName($property);
			call_user_func(array($object, $setterMethodName), $propertyValue);
		} elseif (self::isPropertyAccessible($object, $property, self::ACCESS_PUBLIC)) {
			$object->$property = $propertyValue;
		} else {
			throw new \F3\FLOW3\Reflection\Exception('The property "' . $property . '" on class "' . get_class($object) . '" is not write accessible.', 1231179088); //'
		}
	}
	
	/**
	 * Get declared property names for a given object.
	 * Returns an array of properties which can be get/set with the getProperty and setProperty methods.
	 * 
	 * @param object $object: Object to receive property names for
	 * @return array Array of all declared property names
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function getDeclaredPropertyNames($object) {
		$declaredPropertyNames = array_keys(get_class_vars(get_class($object)));
		
		foreach (get_class_methods($object) as $singleMethod) {
			if (substr($singleMethod, 0, 3) == 'get') {
				$declaredPropertyNames[] = lcfirst(substr($singleMethod, 3));
			}
		}
		
		$properties = array_unique($declaredPropertyNames);
		sort($properties);
		return $properties;
	}
	
	static public function getAllProperties($object) {
		$properties = array();
		foreach (self::getDeclaredPropertyNames($object) as $property) {
			echo $property;
			$properties[$property] = self::getProperty($object, $property);
		}
		return $properties;
	}
	
	/**
	 * Checks if a $property on an $object is accessible by $type.
	 *
	 * @param object $object: The object to do the check on
	 * @param string $property: Name of the property
	 * @param int $type: either self::ACCESS_GET, self::ACCESS_SET or self::ACCESS_PUBLIC.
	 * @return boolean TRUE if property is accessible, FALSE otherwise; FALSE if property does not exist.
	 * @throws F3\FLOW3\Reflection\Exception if called with the wrong $type
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function isPropertyAccessible($object, $property, $type) {
		switch ($type) {
			case self::ACCESS_GET:
				if (is_callable(array($object, self::buildGetterMethodName($property)))) {
					return TRUE;
				}
				break;
			case self::ACCESS_SET:
				if (is_callable(array($object, self::buildSetterMethodName($property)))) {
					return TRUE;
				}
				break;
			case self::ACCESS_PUBLIC:
				//if (property_exists($object, $property)) {
				if (array_key_exists($property, get_class_vars(get_class($object)))) {
					return TRUE;
				}
				break;
			default:
				throw new \F3\FLOW3\Reflection\Exception('isPropertyAccessible called with wrong $type!', 1231176210); //'
		}
		
		return FALSE;
	}
	
	static protected function buildGetterMethodName($property) {
		return 'get' . ucfirst($property);
	}
	static protected function buildSetterMethodName($property) {
		return 'set' . ucfirst($property);
	}
}


?>