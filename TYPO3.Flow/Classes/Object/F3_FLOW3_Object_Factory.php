<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @subpackage Object
 * @version $Id$
 */

/**
 * The Object Factory is mainly used for creating non-singleton objects (ie. with the
 * scope prototype).
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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
	protected $objectRegistry;

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
	 * Injects the object registry
	 *
	 * @param \F3\FLOW3\Object\RegistryInterface $objectRegistry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectRegistry(\F3\FLOW3\Object\RegistryInterface $objectRegistry) {
		$this->objectRegistry = $objectRegistry;
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
	 * @throws \F3\FLOW3\Object\Exception\UnknownObject if an object with the given name does not exist
	 * @throws \F3\FLOW3\Object\Exception\WrongScope if the specified object is not configured as Prototype
	 * @author Robert Lemke <robert@typo3.org>
 	 */
	public function create($objectName) {
		if (!$this->objectManager->isObjectRegistered($objectName)) throw new \F3\FLOW3\Object\Exception\UnknownObject('Object "' . $objectName . '" is not registered.', 1166550023);

		$objectConfiguration = $this->objectManager->getObjectConfiguration($objectName);
		if ($objectConfiguration->getScope() != 'prototype') throw new \F3\FLOW3\Object\Exception\WrongScope('Object "' . $objectName . '" is of scope ' . $objectConfiguration->getScope() . ' but only prototype is supported by create()', 1225385285);

		$arguments = array_slice(func_get_args(), 1);
		$overridingConstructorArguments = $this->getOverridingConstructorArguments($arguments);
		return $this->objectBuilder->createObject($objectName, $objectConfiguration, $overridingConstructorArguments);
	}

	/**
	 * Returns straight-value constructor arguments for an object by creating appropriate
	 * \F3\FLOW3\Object\ConfigurationArgument objects.
	 *
	 * @param array $arguments: Array of argument values. Index must start at "0" for parameter "1" etc.
	 * @return array An array of \F3\FLOW3\Object\ConfigurationArgument which can be passed to the object builder
	 * @author Robert Lemke <robert@typo3.org>
	 * @see create()
	 */
	protected function getOverridingConstructorArguments(array $arguments) {
		$constructorArguments = array();
		foreach ($arguments as $index => $value) {
			$constructorArguments[$index + 1] = new \F3\FLOW3\Object\ConfigurationArgument($index + 1, $value, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		}
		return $constructorArguments;
	}

	/**
	 * Controls cloning of the object factory. Cloning should only be used within unit tests.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		$this->objectRegistry = clone $this->objectRegistry;
		$this->objectBuilder = clone $this->objectBuilder;
	}
}
?>