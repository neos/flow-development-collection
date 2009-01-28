<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

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
 * @subpackage Reflection
 * @version $Id$
 */
/**
 * Provides methods to call appropriate getter/setter on an object given the
 * property name. It does this following these rules:
 * - if the target object is an instance of ArrayAccess, it gets/sets the property
 * - if public getter/setter method exists, call it.
 * - if public property exists, return/set the value of it.
 * - else, throw exception
 *
 * @package
 * @subpackage
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectAccess {

	const ACCESS_GET = 0;
	const ACCESS_SET = 1;
	const ACCESS_PUBLIC = 2;

	/**
	 * Get a property of a given object.
	 * Tries to get the property the following ways:
	 * - if the target object is an instance of ArrayAccess, it gets the property
	 *   on it if it exists.
	 * - if public getter method exists, call it.
	 * - if public property exists, return the value of it.
	 * - else, throw exception
	 *
	 * @param object $object Object to get the property from
	 * @param string $propertyName name of the property to retrieve
	 * @return object Value of the property.
	 * @throws \F3\FLOW3\Reflection\Exception if property was not found or was no string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function getProperty($object, $propertyName) {
		if (!is_string($propertyName)) {
			throw new \F3\FLOW3\Reflection\Exception('Given property is not of type string.', 1231178303);
		}

		if (self::isPropertyAccessible($object, $propertyName, self::ACCESS_GET)) {
			$getterMethodName = self::buildGetterMethodName($propertyName);
			return call_user_func(array($object, $getterMethodName));
		} elseif (self::isPropertyAccessible($object, $propertyName, self::ACCESS_PUBLIC)) {
			return $object->$propertyName;
		} elseif ($object instanceof \ArrayAccess && isset($object[$propertyName])) {
			return $object[$propertyName];
		} else {
			throw new \F3\FLOW3\Reflection\Exception('The property "' . $propertyName . '" on class "' . get_class($object) . '" is not read accessible.', 1231176209);
		}
	}

	/**
	 * Set a property for a given object.
	 * Tries to set the property the following ways:
	 * - if the target object is an instance of ArrayAccess, it sets the property
	 *   on it without checking if it existed.
	 * - if public setter method exists, call it.
	 * - if public property exists, set it directly.
	 * - else, throw exception
	 *
	 * @param object $object Object to get the property from
	 * @param string $propertyName Name of the property to retrieve
	 * @param object $propertyValue Value of the property which should be set.
	 * @return void
	 * @throws \F3\FLOW3\Reflection\Exception if property was not found or was no string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function setProperty($object, $propertyName, $propertyValue) {
		if (!is_string($propertyName)) {
			throw new \F3\FLOW3\Reflection\Exception('Given property is not of type string.', 1231178878);
		}

		if (self::isPropertyAccessible($object, $propertyName, self::ACCESS_SET)) {
			$setterMethodName = self::buildSetterMethodName($propertyName);
			call_user_func(array($object, $setterMethodName), $propertyValue);
		} elseif (self::isPropertyAccessible($object, $propertyName, self::ACCESS_PUBLIC)) {
			$object->$propertyName = $propertyValue;
		} elseif ($object instanceof \ArrayAccess) {
			$object[$propertyName] = $propertyValue;
		} else {
			throw new \F3\FLOW3\Reflection\Exception('The property "' . $propertyName . '" on class "' . get_class($object) . '" is not write accessible.', 1231179088);
		}
	}

	/**
	 * Returns an array of properties which can be get/set with the getProperty
	 * and setProperty methods.
	 * Includes the following properties:
	 * - which can be set through a public setter method.
	 * - public properties which can be directly set.
	 *
	 * @param object $object Object to receive property names for
	 * @return array Array of all declared property names
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo What to do with ArrayAccess
	 */
	static public function getAccessiblePropertyNames($object) {
		$declaredPropertyNames = array_keys(get_class_vars(get_class($object)));

		foreach (get_class_methods($object) as $methodName) {
			if (substr($methodName, 0, 3) === 'get') {
				$declaredPropertyNames[] = lcfirst(substr($methodName, 3));
			}
		}

		$propertyNames = array_unique($declaredPropertyNames);
		sort($propertyNames);
		return $propertyNames;
	}

	/**
	 * Get all properties (names and their current values) of the current
	 * $object that are accessible through this class.
	 *
	 * @param object $object Object to get all properties from.
	 * @return array Associative array of all properties.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo What to do with ArrayAccess
	 */
	static public function getAccessibleProperties($object) {
		$properties = array();
		foreach (self::getAccessiblePropertyNames($object) as $propertyName) {
			$properties[$propertyName] = self::getProperty($object, $propertyName);
		}
		return $properties;
	}

	/**
	 * Checks if a $property on an $object is accessible by $type. For ACCESS_PUBLIC
	 * on ArrayObject instances this returns FALSE.
	 *
	 * @param object $object The object to do the check on
	 * @param string $propertyName Name of the property
	 * @param int $type either self::ACCESS_GET, self::ACCESS_SET or self::ACCESS_PUBLIC.
	 * @return boolean TRUE if property is accessible, FALSE otherwise; FALSE if property does not exist.
	 * @throws F3\FLOW3\Reflection\Exception if called with the wrong $type
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function isPropertyAccessible($object, $propertyName, $type) {
		switch ($type) {
			case self::ACCESS_GET:
				if (is_callable(array($object, self::buildGetterMethodName($propertyName)))) {
					return TRUE;
				}
				break;
			case self::ACCESS_SET:
				if (is_callable(array($object, self::buildSetterMethodName($propertyName)))) {
					return TRUE;
				}
				break;
			case self::ACCESS_PUBLIC:
				if (!($object instanceof \ArrayObject) && array_key_exists($propertyName, get_object_vars($object))) {
					return TRUE;
				}
				break;
			default:
				throw new \F3\FLOW3\Reflection\Exception('isPropertyAccessible called with wrong $type!', 1231176210);
		}

		return FALSE;
	}

	/**
	 * Build the getter method name for a given property by capitalizing the
	 * first letter of the property, and prepending it with "get".
	 *
	 * @param string $property Name of the property
	 * @return string Name of the getter method name
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static protected function buildGetterMethodName($property) {
		return 'get' . ucfirst($property);
	}

	/**
	 * Build the setter method name for a given property by capitalizing the
	 * first letter of the property, and prepending it with "set".
	 *
	 * @param string $property Name of the property
	 * @return string Name of the setter method name
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static protected function buildSetterMethodName($property) {
		return 'set' . ucfirst($property);
	}
}


?>