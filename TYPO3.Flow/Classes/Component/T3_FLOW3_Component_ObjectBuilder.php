<?php
declare(encoding = 'utf-8');

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
 * Component Object Builder
 * 
 * @package		FLOW3
 * @subpackage	Component
 * @version 	$Id:T3_FLOW3_Component_ObjectBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Component_ObjectBuilder implements T3_FLOW3_Component_ObjectBuilderInterface {

	/**
	 * @var T3_FLOW3_Component_Manager A reference to the component manager - used for fetching other component objects while solving dependencies
	 */
	protected $componentManager;
	
	/**
	 * @var array A little registry of component names which are currently being built. Used to prevent endless loops due to circular dependencies.
	 */
	protected $componentsBeingBuilt = array();

	/**
	 * @var array
	 */
	protected $debugMessages = array();
	
	/**
	 * Constructor
	 *
	 * @param  T3_FLOW3_Component_Manager $componentManager: A reference to the component manager - used for fetching other component objects while solving dependencies
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}
	
	/**
	 * Creates and returns a ready-to-use component object of the specified type.
	 * During the building process all depencencies are resolved and injected.
	 *
	 * @param  string $componentName: Name of the component to create a component object for
	 * @param  T3_FLOW3_Component_Configuration $componentConfiguration: The component configuration
	 * @return object
	 * @throws T3_FLOW3_Component_Exception_CannotBuildObject
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObject($componentName, T3_FLOW3_Component_Configuration $componentConfiguration) {
		if (isset ($this->componentsBeingBuilt[$componentName])) throw new T3_FLOW3_Component_Exception_CannotBuildObject('Circular component dependency for component "' . $componentName . '".', 1168505928);
		try {
			$this->componentsBeingBuilt[$componentName] = TRUE;
			$className = $componentConfiguration->getClassName();
			if (!class_exists($className)) throw new T3_FLOW3_Component_Exception_CannotBuildObject('No valid implementation class for component "' . $componentName . '" found while building the component object (Class "' . $className . '" does not exist).', 1173184871);
			
			$constructorArguments = $componentConfiguration->getConstructorArguments();
			if ($componentConfiguration->getAutoWiringMode() == T3_FLOW3_Component_Configuration::AUTOWIRING_MODE_ON) {
				$constructorArguments = $this->autoWireConstructorArguments($constructorArguments, $className);
			}
	
			$valuesForInjection = array();		
			$preparedArguments = array();
			$this->injectConstructorArguments($constructorArguments, $valuesForInjection, $preparedArguments);
	
			$instruction = '$componentObject = new ' . $className .'(' . implode(', ', $preparedArguments) . ');';
			$evalResult = eval($instruction);
			
			if (!is_object($componentObject)) {
				$errorMessage = error_get_last();
				throw new T3_FLOW3_Component_Exception_CannotBuildObject('A parse error ocurred while trying to build a new object of type ' . $className . ' (' . $errorMessage['message'] . '). The evaluated PHP code was: ' . $instruction, 1187164523);
			}
	
			$setterProperties = $componentConfiguration->getProperties();
			$this->injectSetterProperties($setterProperties, $componentObject);
			
			$this->callLifecycleInitializationMethod($componentObject, $componentConfiguration);
			
			unset ($this->componentsBeingBuilt[$componentName]);
			return $componentObject;
		} catch (Exception $exception) {
			unset ($this->componentsBeingBuilt[$componentName]);
			throw $exception;
		}
	}

	/**
	 * If mandatory constructor arguments have not been defined yet, this function tries to autowire 
	 * them if possible.
	 * 
	 * @param  array		$constructorArguments: Array of T3_FLOW3_Component_ConfigurationArgument for the current component
	 * @param  string		$className: The name of the component class which contains the constructor supposed to be analyzed
	 * @return array		The modified array of T3_FLOW3_Component_ConfigurationArgument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autoWireConstructorArguments($constructorArguments, $className) {
		$class = new ReflectionClass($className);
		$constructor = $class->getConstructor();				
		if ($constructor !== NULL) {
			foreach($constructor->getParameters() as $parameterIndex => $parameter) {
				$index = $parameterIndex + 1;
				if (!isset($constructorArguments[$index])) {
					try {
						if ($parameter->isOptional()) {
							$defaultValue = ($parameter->isDefaultValueAvailable()) ? $parameter->getDefaultValue() : NULL;
							$constructorArguments[$index] = new T3_FLOW3_Component_ConfigurationArgument($index, $defaultValue, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
						} elseif ($parameter->getClass() !== NULL) {							
							$constructorArguments[$index] = new T3_FLOW3_Component_ConfigurationArgument($index, $parameter->getClass()->getName(), T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_REFERENCE);
						} elseif ($parameter->allowsNull()) {
							$constructorArguments[$index] = new T3_FLOW3_Component_ConfigurationArgument($index, NULL, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);							
						} else {
							$this->debugMessages[] = 'Really tried hard to autowire parameter $' . $parameter->getName() . ' in ' . $className . '::' . $constructor->getName() . '() but I had no chance.';							
						}
					} catch (ReflectionException $exception) {
						throw new T3_FLOW3_Component_Exception_CannotBuildObject('While trying to autowire the parameter $' . $parameter->getName() . ' of the method ' . $className . '::' . $constructor->getName() .'() a ReflectionException was thrown. Please verify the definition of your constructor method in ' . $constructor->getFileName() . ' line ' . $constructor->getStartLine() .'. Original message: ' . $exception->getMessage(), 1176467813);
					}
				} else {
					$this->debugMessages[] = 'Did not try to autowire parameter $' . $parameter->getName() . ' in ' . $className . '::' . $constructor->getName() . '() because it was already set.';
				}
			}
		} else {
			$this->debugMessages[] = 'Autowiring for class ' . $className . ' disabled because no constructor was found.';
		}
		return $constructorArguments;
	}
	
	/**
	 * Checks and resolves dependencies of the constructor arguments (objects) and prepares an array of constructor
	 * arguments (strings) which can be used in a "new" statement to instantiate the component.
	 *
	 * @param  array		$constructorArguments: Array of T3_FLOW3_Component_ConfigurationArgument for the current component
	 * @param  array		&$valuesForInjection: An empty array passed by reference. Will contain instances of the components which were injected into the constructor
	 * @param  array		&$preparedArguments: An empty array passed by reference: Will contain constructor parameters as strings to be used in a new statement
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectConstructorArguments($constructorArguments, &$valuesForInjection, &$preparedArguments) {
		foreach ($constructorArguments as $index => $constructorArgument) {
			if (is_object($constructorArgument)) {
				if (gettype($constructorArgument->getValue()) == 'integer') {
					$preparedArguments[] = $constructorArgument->getValue();
				} else {
					if ($constructorArgument->getType() === T3_FLOW3_Component_configurationArgument::ARGUMENT_TYPES_REFERENCE) {
						$value = $this->componentManager->getComponent($constructorArgument->getValue());
					} else {
						$value = $constructorArgument->getValue();
					}
					if (is_string($value)) {
						$escapedValue = str_replace("'", "\\'", str_replace('\\', '\\\\', $value));						
						$preparedArguments[] = "'" . $escapedValue . "'";
					} elseif (is_numeric($value)) {
						$preparedArguments[] = $value;
					} elseif ($value === NULL) {
						$preparedArguments[] = 'NULL';
					} else {
						$preparedArguments[] = '$valuesForInjection[' . $index . ']';
						$valuesForInjection[$index] = $value;
					}
				}
			} else {
				$preparedArguments[] = 'NULL';
			}
		}
	}
	
	/**
	 * Checks, resolves and injects dependencies through calling the setter method of the registered properties.
	 *
	 * @param  array		$setterProperties: Array of T3_FLOW3_Component_ConfigurationProperty for the current component
	 * @param  object		$componentObject: The recently created instance of the current component. Dependencies will be injected to it.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectSetterProperties($setterProperties, $componentObject) {
		foreach ($setterProperties as $propertyName => $property) {
			$setterMethodName = 'set' . T3_PHP6_Functions::ucfirst($propertyName);
			switch ($property->getType()) {
				case T3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_REFERENCE:
					$propertyValue = $this->componentManager->getComponent($property->getValue());
				break;
				case T3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE:
					$propertyValue = $property->getValue();
				break;
			}

			if (method_exists($componentObject, $setterMethodName)) {
				$componentObject->$setterMethodName($propertyValue);
			} elseif (method_exists($componentObject, 'setProperty')) {
				$componentObject->setProperty($propertyName, $propertyValue);
			}
		}	
	}
	
	/**
	 * Calls the lifecycle initialization method (if any) of the component object
	 * 
	 * @param  object		$componentObject: The instance of the recently created component.
	 * @param  T3_FLOW3_Component_Configuration $componentConfiguration: The component configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function callLifecycleInitializationMethod($componentObject, T3_FLOW3_Component_Configuration $componentConfiguration) {
		$lifecycleInitializationMethod = $componentConfiguration->getLifecycleInitializationMethod();
		if (method_exists($componentObject, $lifecycleInitializationMethod)) {
			$componentObject->$lifecycleInitializationMethod();
		}
	}
}

?>