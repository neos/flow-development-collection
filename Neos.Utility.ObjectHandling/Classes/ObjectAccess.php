<?php
namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.ObjectHandling package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Exception\PropertyNotAccessibleException;

/**
 * Provides methods to call appropriate getter/setter on an object given the
 * property name. It does this following these rules:
 * - if the target object is an instance of ArrayAccess, it gets/sets the property
 * - if public getter/setter method exists, call it.
 * - if public property exists, return/set the value of it.
 * - else, throw exception
 *
 * Some methods support arrays as well, most notably getProperty() and
 * getPropertyPath().
 *
 */
abstract class ObjectAccess
{
    /**
     * Internal RuntimeCache for getGettablePropertyNames()
     * @var array
     */
    protected static $gettablePropertyNamesCache = [];

    /**
     * Internal RuntimeCache for getPropertyInternal()
     * @var array
     */
    protected static $propertyGetterCache = [];

    const ACCESS_GET = 0;
    const ACCESS_SET = 1;
    const ACCESS_PUBLIC = 2;

    /**
     * Get a property of a given object or array.
     *
     * Tries to get the property the following ways:
     * - if the target is an array, and has this property, we return it.
     * - if super cow powers should be used, fetch value through reflection
     * - if public getter method exists, call it.
     * - if the target object is an instance of ArrayAccess, it gets the property
     *   on it if it exists.
     * - if public property exists, return the value of it.
     * - else, throw exception
     *
     * @param mixed $subject Object or array to get the property from
     * @param string|integer $propertyName Name or index of the property to retrieve
     * @param boolean $forceDirectAccess Directly access property using reflection(!)
     * @return mixed Value of the property
     * @throws \InvalidArgumentException in case $subject was not an object or $propertyName was not a string
     * @throws PropertyNotAccessibleException if the property was not accessible
     */
    public static function getProperty($subject, $propertyName, $forceDirectAccess = false)
    {
        if (!is_object($subject) && !is_array($subject)) {
            throw new \InvalidArgumentException('$subject must be an object or array, ' . gettype($subject) . ' given.', 1237301367);
        }
        if (!is_string($propertyName) && !is_integer($propertyName)) {
            throw new \InvalidArgumentException('Given property name/index is not of type string or integer.', 1231178303);
        }

        $propertyExists = false;
        $propertyValue = self::getPropertyInternal($subject, $propertyName, $forceDirectAccess, $propertyExists);
        if ($propertyExists === true) {
            return $propertyValue;
        }
        throw new PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject was not accessible.', 1263391473);
    }

    /**
     * Gets a property of a given object or array.
     * This is an internal method that does only limited type checking for performance reasons.
     * If you can't make sure that $subject is either of type array or object and $propertyName of type string you should use getProperty() instead.
     *
     * @param mixed $subject Object or array to get the property from
     * @param string $propertyName name of the property to retrieve
     * @param boolean $forceDirectAccess directly access property using reflection(!)
     * @param boolean $propertyExists (by reference) will be set to TRUE if the specified property exists and is gettable
     * @return mixed Value of the property
     * @throws PropertyNotAccessibleException
     * @see getProperty()
     */
    protected static function getPropertyInternal($subject, $propertyName, $forceDirectAccess, &$propertyExists)
    {
        if ($subject === null) {
            return null;
        }
        if (is_array($subject)) {
            if (array_key_exists($propertyName, $subject)) {
                $propertyExists = true;
                return $subject[$propertyName];
            }
            return null;
        } elseif (!is_object($subject)) {
            return null;
        }

        $propertyExists = true;
        $className = TypeHandling::getTypeForValue($subject);

        if ($forceDirectAccess === true) {
            if (property_exists($className, $propertyName)) {
                $propertyReflection = new \ReflectionProperty($className, $propertyName);
                $propertyReflection->setAccessible(true);
                return $propertyReflection->getValue($subject);
            } elseif (property_exists($subject, $propertyName)) {
                return $subject->$propertyName;
            } else {
                throw new PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject does not exist.', 1302855001);
            }
        }

        if ($subject instanceof \stdClass) {
            if (array_key_exists($propertyName, get_object_vars($subject))) {
                return $subject->$propertyName;
            } else {
                $propertyExists = false;
                return null;
            }
        }

        $cacheIdentifier = $className . '|' . $propertyName;
        self::initializePropertyGetterCache($cacheIdentifier, $subject, $propertyName);

        if (isset(self::$propertyGetterCache[$cacheIdentifier]['accessorMethod'])) {
            $method = self::$propertyGetterCache[$cacheIdentifier]['accessorMethod'];
            return $subject->$method();
        } elseif (isset(self::$propertyGetterCache[$cacheIdentifier]['publicProperty'])) {
            return $subject->$propertyName;
        }

        if (($subject instanceof \ArrayAccess) && !($subject instanceof \SplObjectStorage)) {
            if (isset($subject[$propertyName])) {
                return $subject[$propertyName];
            }
        }

        $propertyExists = false;
        return null;
    }

    /**
     * @param string $cacheIdentifier
     * @param mixed $subject
     * @param string $propertyName
     * @return void
     */
    protected static function initializePropertyGetterCache($cacheIdentifier, $subject, $propertyName)
    {
        if (isset(self::$propertyGetterCache[$cacheIdentifier])) {
            return;
        }
        self::$propertyGetterCache[$cacheIdentifier] = [];
        $uppercasePropertyName = ucfirst($propertyName);
        $getterMethodNames = ['get' . $uppercasePropertyName, 'is' . $uppercasePropertyName, 'has' . $uppercasePropertyName];
        foreach ($getterMethodNames as $getterMethodName) {
            if (is_callable([$subject, $getterMethodName])) {
                self::$propertyGetterCache[$cacheIdentifier]['accessorMethod'] = $getterMethodName;
                return;
            }
        }
        if ($subject instanceof \ArrayAccess) {
            return;
        }
        if (array_key_exists($propertyName, get_object_vars($subject))) {
            self::$propertyGetterCache[$cacheIdentifier]['publicProperty'] = $propertyName;
        }
    }

    /**
     * Gets a property path from a given object or array.
     *
     * If propertyPath is "bla.blubb", then we first call getProperty($object, 'bla'),
     * and on the resulting object we call getProperty(..., 'blubb').
     *
     * For arrays the keys are checked likewise.
     *
     * @param mixed $subject An object or array
     * @param string $propertyPath
     * @return mixed Value of the property
     */
    public static function getPropertyPath($subject, $propertyPath)
    {
        $propertyPathSegments = explode('.', $propertyPath);
        foreach ($propertyPathSegments as $pathSegment) {
            $propertyExists = false;
            $propertyValue = self::getPropertyInternal($subject, $pathSegment, false, $propertyExists);
            if ($propertyExists !== true && (is_array($subject) || $subject instanceof \ArrayAccess) && isset($subject[$pathSegment])) {
                $subject = $subject[$pathSegment];
            } else {
                $subject = $propertyValue;
            }
        }
        return $subject;
    }

    /**
     * Set a property for a given object.
     * Tries to set the property the following ways:
     * - if target is an array, set value
     * - if super cow powers should be used, set value through reflection
     * - if public setter method exists, call it.
     * - if public property exists, set it directly.
     * - if the target object is an instance of ArrayAccess, it sets the property
     *   on it without checking if it existed.
     * - else, return FALSE
     *
     * @param mixed $subject The target object or array
     * @param string|integer $propertyName Name or index of the property to set
     * @param mixed $propertyValue Value of the property
     * @param boolean $forceDirectAccess directly access property using reflection(!)
     * @return boolean TRUE if the property could be set, FALSE otherwise
     * @throws \InvalidArgumentException in case $object was not an object or $propertyName was not a string
     */
    public static function setProperty(&$subject, $propertyName, $propertyValue, $forceDirectAccess = false)
    {
        if (is_array($subject)) {
            $subject[$propertyName] = $propertyValue;
            return true;
        }

        if (!is_object($subject)) {
            throw new \InvalidArgumentException('subject must be an object or array, ' . gettype($subject) . ' given.', 1237301368);
        }
        if (!is_string($propertyName) && !is_integer($propertyName)) {
            throw new \InvalidArgumentException('Given property name/index is not of type string or integer.', 1231178878);
        }

        if ($forceDirectAccess === true) {
            $className = TypeHandling::getTypeForValue($subject);
            if (property_exists($className, $propertyName)) {
                $propertyReflection = new \ReflectionProperty($className, $propertyName);
                $propertyReflection->setAccessible(true);
                $propertyReflection->setValue($subject, $propertyValue);
            } else {
                $subject->$propertyName = $propertyValue;
            }
        } elseif (is_callable([$subject, $setterMethodName = self::buildSetterMethodName($propertyName)])) {
            $subject->$setterMethodName($propertyValue);
        } elseif ($subject instanceof \ArrayAccess) {
            $subject[$propertyName] = $propertyValue;
        } elseif (array_key_exists($propertyName, get_object_vars($subject))) {
            $subject->$propertyName = $propertyValue;
        } else {
            return false;
        }
        return true;
    }

    /**
     * Returns an array of properties which can be get with the getProperty()
     * method.
     * Includes the following properties:
     * - which can be get through a public getter method.
     * - public properties which can be directly get.
     *
     * @param object $object Object to receive property names for
     * @return array Array of all gettable property names
     * @throws \InvalidArgumentException
     */
    public static function getGettablePropertyNames($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1237301369);
        }
        if ($object instanceof \stdClass) {
            $declaredPropertyNames = array_keys(get_object_vars($object));
            $className = 'stdClass';
            unset(self::$gettablePropertyNamesCache[$className]);
        } else {
            $className = TypeHandling::getTypeForValue($object);
            $declaredPropertyNames = array_keys(get_class_vars($className));
        }

        if (!isset(self::$gettablePropertyNamesCache[$className])) {
            foreach (get_class_methods($object) as $methodName) {
                if (is_callable([$object, $methodName])) {
                    if (substr($methodName, 0, 2) === 'is' && strlen($methodName) > 2) {
                        $declaredPropertyNames[] = lcfirst(substr($methodName, 2));
                    }
                    if (substr($methodName, 0, 3) === 'get' && strlen($methodName) > 3) {
                        $declaredPropertyNames[] = lcfirst(substr($methodName, 3));
                    }
                    if (substr($methodName, 0, 3) === 'has' && strlen($methodName) > 3) {
                        $declaredPropertyNames[] = lcfirst(substr($methodName, 3));
                    }
                }
            }

            $propertyNames = array_unique($declaredPropertyNames);
            sort($propertyNames);
            self::$gettablePropertyNamesCache[$className] = $propertyNames;
        }
        return self::$gettablePropertyNamesCache[$className];
    }

    /**
     * Returns an array of properties which can be set with the setProperty()
     * method.
     * Includes the following properties:
     * - which can be set through a public setter method.
     * - public properties which can be directly set.
     *
     * @param object $object Object to receive property names for
     * @return array Array of all settable property names
     * @throws \InvalidArgumentException
     */
    public static function getSettablePropertyNames($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1264022994);
        }
        if ($object instanceof \stdClass) {
            $declaredPropertyNames = array_keys(get_object_vars($object));
        } else {
            $className = TypeHandling::getTypeForValue($object);
            $declaredPropertyNames = array_keys(get_class_vars($className));
        }

        foreach (get_class_methods($object) as $methodName) {
            if (substr($methodName, 0, 3) === 'set' && strlen($methodName) > 3 && is_callable([$object, $methodName])) {
                $declaredPropertyNames[] = lcfirst(substr($methodName, 3));
            }
        }

        $propertyNames = array_unique($declaredPropertyNames);
        sort($propertyNames);
        return $propertyNames;
    }

    /**
     * Tells if the value of the specified property can be set by this Object Accessor.
     *
     * @param object $object Object containing the property
     * @param string $propertyName Name of the property to check
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public static function isPropertySettable($object, $propertyName)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1259828920);
        }

        $className = TypeHandling::getTypeForValue($object);
        if ($object instanceof \stdClass && array_search($propertyName, array_keys(get_object_vars($object))) !== false) {
            return true;
        } elseif (array_search($propertyName, array_keys(get_class_vars($className))) !== false) {
            return true;
        }
        return is_callable([$object, self::buildSetterMethodName($propertyName)]);
    }

    /**
     * Tells if the value of the specified property can be retrieved by this Object Accessor.
     *
     * @param object $object Object containing the property
     * @param string $propertyName Name of the property to check
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public static function isPropertyGettable($object, $propertyName)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1259828921);
        }
        if ($object instanceof \ArrayAccess && isset($object[$propertyName]) === true) {
            return true;
        } elseif ($object instanceof \stdClass && array_search($propertyName, array_keys(get_object_vars($object))) !== false) {
            return true;
        }
        $uppercasePropertyName = ucfirst($propertyName);
        if (is_callable([$object, 'get' . $uppercasePropertyName])) {
            return true;
        }
        if (is_callable([$object, 'is' . $uppercasePropertyName])) {
            return true;
        }
        if (is_callable([$object, 'has' . $uppercasePropertyName])) {
            return true;
        }
        $className = TypeHandling::getTypeForValue($object);
        return (array_search($propertyName, array_keys(get_class_vars($className))) !== false);
    }

    /**
     * Get all properties (names and their current values) of the current
     * $object that are accessible through this class.
     *
     * @param object $object Object to get all properties from.
     * @return array Associative array of all properties.
     * @throws \InvalidArgumentException
     * @todo What to do with ArrayAccess
     */
    public static function getGettableProperties($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1237301370);
        }
        $properties = [];
        foreach (self::getGettablePropertyNames($object) as $propertyName) {
            $propertyExists = false;
            $propertyValue = self::getPropertyInternal($object, $propertyName, false, $propertyExists);
            if ($propertyExists === true) {
                $properties[$propertyName] = $propertyValue;
            }
        }
        return $properties;
    }

    /**
     * Build the setter method name for a given property by capitalizing the
     * first letter of the property, and prepending it with "set".
     *
     * @param string $propertyName Name of the property
     * @return string Name of the setter method name
     */
    public static function buildSetterMethodName($propertyName)
    {
        return 'set' . ucfirst($propertyName);
    }
}
