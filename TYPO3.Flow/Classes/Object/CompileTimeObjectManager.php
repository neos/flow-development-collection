<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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

use \F3\FLOW3\Object\Configuration\Configuration;
use \F3\FLOW3\Object\Configuration\ConfigurationProperty as Property;
use \F3\FLOW3\Reflection\ObjectAccess;

/**
 * A specialized Object Manager which is able to do some basic dependency injection for
 * singleton scoped objets. This Object Manager is used during compile time when the proxy
 * class based DI mechanism is not yet available.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @proxy disable
 */
class CompileTimeObjectManager extends ObjectManager {

	/**
	 * @var array
	 */
	protected $objectConfigurations;

	/**
	 * @var array
	 */
	protected $objectNameBuildStack = array();

	/**
	 * Sets the object configurations which were previously built by the ConfigurationBuilder.
	 *
	 * @param array $objectConfigurations
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurations(array $objectConfigurations) {
		$this->objectConfigurations = $objectConfigurations;
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * This specialized get() method is able to do setter injection for properties defined in the object configuration
	 * of the specified object.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function get($objectName) {
		if (isset($this->objects[$objectName]['i'])) {
			return $this->objects[$objectName]['i'];
		}

		if (isset($this->objectConfigurations[$objectName]) && count($this->objectConfigurations[$objectName]->getArguments()) > 0) {
			throw new Exception\CannotBuildObjectException('Cannot build object "' . $objectName . '" because constructor injection is not available in the compile time Object Manager. Refactor your code to use setter injection instead. Configuration source: ' . $this->objectConfigurations[$objectName]->getConfigurationSourceHint() . '. Build stack: ' . implode(', ', $this->objectNameBuildStack), 1297090026);
		}
		if ($this->objects[$objectName]['s'] !== Configuration::SCOPE_SINGLETON) {
			throw new Exception\CannotBuildObjectException('Cannot build object "' . $objectName . '" because the get() method in the compile time Object Manager only supports singletons.', 1297090027);
		}

		$this->objectNameBuildStack[] = $objectName;

		$object = parent::get($objectName);
		foreach ($this->objectConfigurations[$objectName]->getProperties() as $propertyName => $property) {
			if ($property->getAutowiring() !== Configuration::AUTOWIRING_MODE_ON) {
				continue;
			}
			switch ($property->getType()) {
				case Property::PROPERTY_TYPES_STRAIGHTVALUE:
					$value = $property->getValue();
				break;
				case Property::PROPERTY_TYPES_SETTING:
					$value = \F3\FLOW3\Utility\Arrays::getValueByPath($this->settings, explode('.', $property->getValue()));
				break;
				case Property::PROPERTY_TYPES_OBJECT:
					$propertyObjectName = $property->getValue();
					if (!is_string($propertyObjectName)) {
						throw new Exception\CannotBuildObjectException('The object definition of "' . $objectName . '::' . $propertyName . '" is too complex for the compile time Object Manager. You can only use plain object names, not factories and the like. Check configuration in ' . $this->objectConfigurations[$objectName]->getConfigurationSourceHint() . ' and objects which depend on ' . $objectName. '.', 1297099659);
					}
					$value = $this->get($propertyObjectName);
				break;
				default:
					throw new Exception\CannotBuildObjectException('Invalid property type.', 1297090029);
				break;
			}

			if (method_exists($object, $setterMethodName = 'inject' . ucfirst($propertyName))) {
				$object->$setterMethodName($value);
			} elseif (method_exists($object, $setterMethodName = 'set' . ucfirst($propertyName))) {
				$object->$setterMethodName($value);
			} else {
				throw new Exception\UnresolvedDependenciesException('Could not inject configured property "' . $propertyName . '" into "' . $objectName . '" Because no injection method exists. Configuration source: ' . $this->objectConfigurations[$objectName]->getConfigurationSourceHint() . '.', 1297110953);
			}
		}

		$initializationLifecycleMethodName = $this->objectConfigurations[$objectName]->getLifecycleInitializationMethodName();
		if (method_exists($object, $initializationLifecycleMethodName)) {
			$object->$initializationLifecycleMethodName();
		}

		$shutdownLifecycleMethodName = $this->objectConfigurations[$objectName]->getLifecycleShutdownMethodName();
		if (method_exists($object, $shutdownLifecycleMethodName)) {
			$this->shutdownObjects[$object] = $shutdownLifecycleMethodName;
		}

		array_pop($this->objectNameBuildStack);
		return $object;
	}
}
?>