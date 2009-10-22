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

/**
 * The Object Factory is mainly used for creating non-singleton objects (ie. with the
 * scope prototype).
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Factory implements \F3\FLOW3\Object\FactoryInterface {

	/**
	 * A reference to the object manager
	 *
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\RegistryInterface Holds an instance of the Object Object Cache
	 */
	protected $singletonObjectsRegistry;

	/**
	 * @var \F3\FLOW3\Object\Builder Holds an instance of the Object Object Builder
	 */
	protected $objectBuilder;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the object builder
	 *
	 * @param \F3\FLOW3\Object\Builder $objectBuilder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectBuilder(\F3\FLOW3\Object\Builder $objectBuilder) {
		$this->objectBuilder = $objectBuilder;
	}

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * This factory method can only create objects of the scope prototype.
	 * Singleton objects must be either injected by some type of Dependency Injection or
	 * if that is not possible, be retrieved by the getObject() method of the
	 * Object Manager
	 *
	 * You must use either Dependency Injection or this factory method for instantiation
	 * of your objects if you need FLOW3's object management capabilities (including
	 * AOP, Security and Persistence). It is absolutely okay and often advisable to
	 * use the "new" operator for instantiation in your automated tests.
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @throws \InvalidArgumentException if the object name starts with a backslash
	 * @throws \F3\FLOW3\Object\Exception\UnknownObject if an object with the given name does not exist
	 * @throws \F3\FLOW3\Object\Exception\WrongScope if the specified object is not configured as Prototype
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function create($objectName) {
		if ($objectName{0} === '\\') throw new \InvalidArgumentException('The object name must not start with a backslash, "' . $objectName . '" given.', 1243272770);
		if (!$this->objectManager->isObjectRegistered($objectName)) throw new \F3\FLOW3\Object\Exception\UnknownObject('Object "' . $objectName . '" is not registered.', 1166550023);

		$objectConfiguration = $this->objectManager->getObjectConfiguration($objectName);
		if ($objectConfiguration->getScope() != 'prototype') throw new \F3\FLOW3\Object\Exception\WrongScope('Object "' . $objectName . '" is of scope ' . $objectConfiguration->getScope() . ' but only prototype is supported by create()', 1225385285);

		$overridingArguments = self::convertArgumentValuesToArgumentObjects(array_slice(func_get_args(), 1));
		$object =  $this->objectBuilder->createObject($objectName, $objectConfiguration, $overridingArguments);
		$this->objectManager->registerShutdownObject($object, $objectConfiguration->getLifecycleShutdownMethodName());
		return $object;
	}

	/**
	 * Returns straight-value constructor arguments by creating appropriate
	 * \F3\FLOW3\Object\Configuration\ConfigurationArgument objects.
	 *
	 * @param array $argumentValues Array of argument values. Index must start at "0" for parameter "1" etc.
	 * @return array An array of \F3\FLOW3\Object\Configuration\ConfigurationArgument which can be passed to the object builder
	 * @author Robert Lemke <robert@typo3.org>
	 * @see create()
	 */
	static protected function convertArgumentValuesToArgumentObjects(array $argumentValues) {
		$argumentObjects = array();
		foreach ($argumentValues as $index => $value) {
			$argumentObjects[$index + 1] = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($index + 1, $value, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		}
		return $argumentObjects;
	}

	/**
	 * Controls cloning of the object factory. Cloning should only be used within unit tests.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		$this->objectBuilder = clone $this->objectBuilder;
	}
}
?>