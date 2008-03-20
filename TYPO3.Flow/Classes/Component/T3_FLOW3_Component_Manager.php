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
 * @subpackage Component
 * @version $Id$
 */

/**
 * Implementation of the default TYPO3 Component Manager
 *
 * @package FLOW3
 * @subpackage Component
 * @version $Id:T3_FLOW3_Component_Manager.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Component_Manager implements T3_FLOW3_Component_ManagerInterface {

	/**
	 * @var string Name of the current context
	 */
	protected $context = 'Development';

	/**
	 * @var T3_FLOW3_Component_ObjectCacheInterface Holds an instance of the Component Object Cache
	 */
	protected $componentObjectCache;

	/**
	 * @var T3_FLOW3_Component_ObjectBuilderInterface Holds an instance of the Component Object Builder
	 */
	protected $componentObjectBuilder;

	/**
	 * @var array An array of all registered components. The case sensitive component name is the key, a lower-cased variant is the value.
	 */
	protected $registeredComponents = array();

	/**
	 * @var array An array of all registered component configurations
	 */
	protected $componentConfigurations = array();

	/**
	 * Constructor. Instantiates the object cache and object builder.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->componentObjectCache = new T3_FLOW3_Component_TransientObjectCache();
		$this->componentObjectBuilder = new T3_FLOW3_Component_ObjectBuilder($this);

		$this->registerComponent('T3_FLOW3_Component_ManagerInterface', __CLASS__, $this);
	}

	/**
	 * Sets the Component Manager to a specific context. All operations related to components
	 * will be carried out based on the configuration for the current context.
	 *
	 * The context should be set as early as possible, preferably before any component has been
	 * instantiated.
	 *
	 * By default the context is set to "default". Although the context can be freely chosen,
	 * the following contexts are explicitly supported by FLOW3:
	 * "Production", "Development", "Testing", "Profiling", "Staging"
	 *
	 * @param  string $context: Name of the context
	 * @return void
	 * @throws InvalidArgumentException if $context is not a valid string.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext($context) {
		if (!is_string($context)) throw new InvalidArgumentException();
		$this->context = $context;
	}

	/**
	 * Returns the name of the currently set context.
	 *
	 * @return  string Name of the current context
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Returns an instance of the component specified by $componentName.
	 * Always ask this method for class instances instead of using the "new"
	 * operator!
	 *
	 * Note: If neccessary (while using legacy classes for example), you may
	 *       pass additional parameters which are then used as parameters passed
	 *       to the constructor of the component class. However, you whould only
	 *       use this feature if your parameters are truly dynamic. Otherwise just
	 *       configure them in your Components.ini file.
	 *
	 * @param  string $componentName: The unique identifier (name) of the component to return an instance of
	 * @return object The component instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws InvalidArgumentException if $componentName is not a string
	 * @throws T3_FLOW3_Component_Exception_UnknownComponent if a component with the given name does not exist
	 */
	public function getComponent($componentName) {
		if (!is_string($componentName)) throw new InvalidArgumentException('The component name must be of type string, ' . gettype($componentName) . ' given.', 1181908191);
		if (!$this->isComponentRegistered($componentName)) throw new T3_FLOW3_Component_Exception_UnknownComponent('Component "' . $componentName . '" is not registered.', 1166550023);

		$componentConfiguration = $this->componentConfigurations[$componentName];
		$overridingConstructorArguments = $this->getOverridingConstructorArguments(array_slice(func_get_args(), 1), $componentConfiguration);
		$scope = $this->getComponentScope($componentName, $componentConfiguration);
		switch ($scope) {
			case 'prototype' :
				$componentObject = $this->componentObjectBuilder->createComponentObject($componentName, $componentConfiguration, $overridingConstructorArguments);
				break;
			case 'singleton' :
				if ($this->componentObjectCache->componentObjectExists($componentName)) {
					$componentObject = $this->componentObjectCache->getComponentObject($componentName);
				} else {
					$componentObject = $this->componentObjectBuilder->createComponentObject($componentName, $componentConfiguration, $overridingConstructorArguments);
					$this->componentObjectCache->putComponentObject($componentName, $componentObject);
				}
				break;
			default :
				throw new T3_FLOW3_Component_Exception('Support for scope "' . $scope .'" has not been implemented (yet)', 1167484148);
		}

		return $componentObject;
	}

	/**
	 * Registers the given class as a component
	 *
	 * @param string $componentName: The unique identifier of the component
	 * @param string $className: The class name which provides the functionality for this component. Same as component name by default.
	 * @param object $componentObject: If the component has been instantiated prior to registration (which should be avoided whenever possible), it can be passed here.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Component_Exception_ComponentAlreadyRegistered if the component has already been registered
	 * @throws T3_FLOW3_Component_Exception_InvalidComponentObject if the passed $componentObject is not a valid instance of $className
	 */
	public function registerComponent($componentName, $className = NULL, $componentObject = NULL) {
		if ($this->isComponentRegistered($componentName)) throw new T3_FLOW3_Component_Exception_ComponentAlreadyRegistered('The component ' . $componentName . ' is already registered.', 1184160573);
		if ($className === NULL) {
			$className = $componentName;
		}
		if (!class_exists($className, TRUE)) throw new T3_FLOW3_Component_Exception_UnknownClass('The specified class "' . $className . '" does not exist (or is no class) and therefore cannot be registered as a component.', 1200239063);

		$class = new T3_FLOW3_Reflection_Class($className);
		if ($class->isAbstract()) throw new T3_FLOW3_Component_Exception_InvalidClass('Cannot register the abstract class "' . $className . '" as a component.', 1200239129);

		if ($componentObject !== NULL) {
			if (!is_object($componentObject) || !$componentObject instanceof $className) throw new T3_FLOW3_Component_Exception_InvalidComponentObject('The component instance must be a valid instance of the specified class (' . $className . ').', 1183742379);
			$this->componentObjectCache->putComponentObject($componentName, $componentObject);
		}
		$this->componentConfigurations[$componentName] = new T3_FLOW3_Component_Configuration($componentName, $className);
		$this->registeredComponents[$componentName] = T3_PHP6_Functions::strtolower($componentName);
	}

	/**
	 * Register the given interface as a component type
	 *
	 * @param  string $componentType: The unique identifier of the component (-type)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentType($componentName) {
		$componentConfiguration = new T3_FLOW3_Component_Configuration($componentName);
		$className = $this->getDefaultImplementationClassNameForInterface($componentName);
		if ($className !== FALSE) {
			$componentConfiguration->setClassName($className);
		}
		$this->registeredComponents[$componentName] = T3_PHP6_Functions::strtolower($componentName);
		$this->componentConfigurations[$componentName] = $componentConfiguration;
	}

	/**
	 * Unregisters the specified component
	 *
	 * @param string $componentName: The explicit component name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Component_Exception_UnknownComponent if the specified component has not been registered before
	 */
	public function unregisterComponent($componentName) {
		if (!$this->isComponentRegistered($componentName)) throw new T3_FLOW3_Component_Exception_UnknownComponent('Component "' . $componentName . '" is not registered.', 1167473433);
		if ($this->componentObjectCache->componentObjectExists($componentName)) {
			$this->componentObjectCache->removeComponentObject($componentName);
		}
		unset($this->registeredComponents[$componentName]);
		unset($this->componentConfigurations[$componentName]);
	}

	/**
	 * Returns TRUE if a component with the given name has already
	 * been registered.
	 *
	 * @param  string $componentName: Name of the component
	 * @return boolean TRUE if the component has been registered, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws InvalidArgumentException if $componentName is not a valid string
	 */
	public function isComponentRegistered($componentName) {
		if (!is_string($componentName)) throw new InvalidArgumentException('The component name must be of type string, ' . gettype($componentName) . ' given.', 1181907931);
		return key_exists($componentName, $this->registeredComponents);
	}

	/**
	 * Returns the case sensitive component name of a component specified by a
	 * case insensitive component name. If no component of that name exists,
	 * FALSE is returned.
	 *
	 * In general, the case sensitive variant is used everywhere in FLOW3,
	 * however there might be special situations in which the
	 * case sensitive name is not available. This method helps you in these
	 * rare cases.
	 *
	 * @param  string $caseInsensitiveComponentName: The component name in lower-, upper- or mixed case
	 * @return mixed Either the mixed case component name or FALSE if no component of that name was found.
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws InvalidArgumentException if $caseInsensitiveComponentName is not a valid string
	 */
	public function getCaseSensitiveComponentName($caseInsensitiveComponentName) {
		if (!is_string($caseInsensitiveComponentName)) throw new InvalidArgumentException('The component name must be of type string, ' . gettype($caseInsensitiveComponentName) . ' given.', 1186655552);
		return array_search(T3_PHP6_Functions::strtolower($caseInsensitiveComponentName), $this->registeredComponents);
	}

	/**
	 * Returns an array of component names of all registered components.
	 * The mixed case component name are used as the array's keys while each
	 * value is the lower cased variant of its respective key.
	 *
	 * @return array An array of component names - mixed case in the key and lower case in the value.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegisteredComponents() {
		return $this->registeredComponents;
	}

	/**
	 * Returns an array of configuration objects for all registered components.
	 *
	 * @return arrray Array of T3_FLOW3_Component_Configuration objects, indexed by component name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getComponentConfigurations() {
		return $this->componentConfigurations;
	}

	/**
	 * Returns the configuration object of a certain component
	 *
	 * @param string $componentName: Name of the component to fetch the configuration for
	 * @return T3_FLOW3_Component_Configuration The component configuration
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Component_Exception_UnknownComponent if the specified component has not been registered
	 */
	public function getComponentConfiguration($componentName) {
		if (!$this->isComponentRegistered($componentName)) throw new T3_FLOW3_Component_Exception_UnknownComponent('Component "' . $componentName . '" is not registered.', 1167993004);
		return clone $this->componentConfigurations[$componentName];
	}

	/**
	 * Sets the component configurations for all components found in the
	 * $newComponentConfigurations array.
	 *
	 * @param array $newComponentConfigurations: Array of $componentName => T3_FLOW3_Component_configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setComponentConfigurations(array $newComponentConfigurations) {
		foreach ($newComponentConfigurations as $newComponentConfiguration) {
			if (!$newComponentConfiguration instanceof T3_FLOW3_Component_Configuration) throw new InvalidArgumentException('The new component configuration must be an instance of T3_FLOW3_Component_Configuration', 1167826954);
			$componentName = $newComponentConfiguration->getComponentName();
			if (!key_exists($componentName, $this->componentConfigurations) || $this->componentConfigurations[$componentName] !== $newComponentConfiguration) {
				$this->setComponentConfiguration($newComponentConfiguration);
			}
		}
	}

	/**
	 * Sets the component configuration for a specific component.
	 *
	 * @param T3_FLOW3_Component_Configuration $newComponentConfiguration: The new component configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setComponentConfiguration(T3_FLOW3_Component_Configuration $newComponentConfiguration) {
		$componentName = $newComponentConfiguration->getComponentName();
		$this->componentConfigurations[$newComponentConfiguration->getComponentName()] = clone $newComponentConfiguration;
	}

	/**
	 * Sets the name of the class implementing the specified component.
	 * This is a convenience method which loads the configuration of the given
	 * component, sets the class name and saves the configuration again.
	 *
	 * @param string $componentName: Name of the component to set the class name for
	 * @param string $className: Name of the class to set
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Component_Exception_UnknownComponent on trying to set the class name of an unknown component
	 * @throws T3_FLOW3_Component_Exception_UnknownClass if the class does not exist
	 */
	public function setComponentClassName($componentName, $className) {
		if (!$this->isComponentRegistered($componentName)) throw new T3_FLOW3_Component_Exception_UnknownComponent('Tried to set class name of non existent component "' . $componentName . '"', 1185524488);
		if (!class_exists($className)) throw new T3_FLOW3_Component_Exception_UnknownClass('Tried to set the class name of component "' . $componentName . '" but a class "' . $className . '" does not exist.', 1185524499);
		$componentConfiguration = $this->getComponentConfiguration($componentName);
		$componentConfiguration->setClassName($className);
		$this->setComponentConfiguration($componentConfiguration);
	}

	/**
	 * Searches for and returns the class name of the default implementation of the given
	 * interface name. If no class implementing the interface was found or more than one
	 * implementation was found in the package defining the interface, FALSE is returned.
	 *
	 * @param string $interfaceName: Name of the interface
	 * @return mixed Either the class name of the default implementation for the component type or FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Component_Exception_UnknownInterface if the specified interface does not exist.
	 */
	public function getDefaultImplementationClassNameForInterface($interfaceName) {
		if (!interface_exists($interfaceName, TRUE)) throw new T3_FLOW3_Component_Exception_UnknownInterface('The interface "' . $interfaceName . '" does not exist.', 1203578679);
		$classNamesFound = array();
		foreach ($this->componentConfigurations as $componentConfiguration) {
			$className = $componentConfiguration->getClassName();
			if (class_exists($className)) {
				$class = new ReflectionClass($className);
				if ($class->implementsInterface($interfaceName) && !$class->isAbstract()) {
					$classNamesFound[] = $className;
				}
			}
		}
		return (count($classNamesFound) == 1 ? $classNamesFound[0] : FALSE);
	}

	/**
	 * Searches for and returns all class names of implementations of the given component type
	 * (interface name). If no class implementing the interface was found, FALSE is returned.
	 *
	 * @param string $interfaceName: Name of the interface
	 * @return array An array of class names of the default implementation for the component type
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Component_Exception_UnknownInterface if the given interface does not exist
	 */
	public function getAllImplementationClassNamesForInterface($interfaceName) {
		if (!interface_exists($interfaceName)) throw new T3_FLOW3_Component_Exception_UnknownInterface('Cannot find implementations for non-existing interface "' . $interfaceName . '".', 1176468683);

		$classNamesFound = array();
		foreach ($this->componentConfigurations as $componentConfiguration) {
			$className = $componentConfiguration->getClassName();
			if (class_exists($className)) {
				$class = new ReflectionClass($className);
				if ($class->implementsInterface($interfaceName) && !$class->isAbstract()) {
					$classNamesFound[$className] = $className;
				}
			}
		}
		return $classNamesFound;
	}

	/**
	 * Returns straight-value constructor arguments for a component by creating approriate
	 * T3_FLOW3_Component_ConfigurationArgument objects.
	 *
	 * @param array $arguments: Array of argument values. Index must start at "0" for parameter "1" etc.
	 * @param T3_FLOW3_Component_Configuration $componentConfiguration: The component configuration of the component in question
	 * @return array An array of T3_FLOW3_Component_ConfigurationArgument which can be passed to the object builder
	 * @author Robert Lemke <robert@typo3.org>
	 * @see getComponent()
	 */
	protected function getOverridingConstructorArguments(array $arguments, T3_FLOW3_Component_Configuration $componentConfiguration) {
		$constructorArguments = array();
		foreach ($arguments as $index => $value) {
			$constructorArguments[$index + 1] = new T3_FLOW3_Component_ConfigurationArgument($index + 1, $value, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		}
		return $constructorArguments;
	}

	/**
	 * Returns the scope of the specified component. If it is not defined in the component
	 * configuration, the scope is determined from the annotation.
	 *
	 * @param string $componentName: Name of the component
	 * @param T3_FLOW3_Component_Configuration $componentConfiguration: The component configuration
	 * @return string The scope
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo needs cleanup
	 */
	protected function getComponentScope($componentName, $componentConfiguration) {
		$scope = $componentConfiguration->getScope();
		if ($scope === '') {
			$class = new T3_FLOW3_Reflection_Class($componentConfiguration->getClassName());
			$scope = $class->isTaggedWith('scope') ? trim(implode('', $class->getTagValues('scope'))) : 'singleton';
		}
		return $scope;
	}

	/**
	 * Controls cloning of the component manager. Cloning should only be used within unit tests.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		$this->componentObjectCache = clone $this->componentObjectCache;
		$this->componentObjectBuilder = clone $this->componentObjectBuilder;
		$this->componentObjectCache->putComponentObject('T3_FLOW3_Component_ManagerInterface', $this);
	}
}

?>