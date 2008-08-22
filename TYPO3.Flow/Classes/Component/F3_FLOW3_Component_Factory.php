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
 * The Component Factory is mainly used for creating non-singleton components (ie. with the
 * scope prototype).
 *
 * @package FLOW3
 * @subpackage Component
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Component_Factory implements F3_FLOW3_Component_FactoryInterface {

	/**
	 * A reference to the component manager
	 *
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Component_ObjectCacheInterface Holds an instance of the Component Object Cache
	 */
	protected $componentObjectCache;

	/**
	 * @var F3_FLOW3_Component_ObjectBuilder Holds an instance of the Component Object Builder
	 */
	protected $componentObjectBuilder;

	/**
	 * Injects the component manager
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentManager(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Injects the component object builder
	 *
	 * @param F3_FLOW3_Component_ObjectBuilder $componentObjectBuilder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentObjectBuilder(F3_FLOW3_Component_ObjectBuilder $componentObjectBuilder) {
		$this->componentObjectBuilder = $componentObjectBuilder;
	}

	/**
	 * Injects the component object cache
	 *
	 * @param F3_FLOW3_Component_ObjectCacheInterface $componentObjectCache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentObjectCache(F3_FLOW3_Component_ObjectCacheInterface $componentObjectCache) {
		$this->componentObjectCache = $componentObjectCache;
	}

	/**
	 * Returns an instance of the component specified by $componentName.
	 *
	 * This factory method should mainly be used for components of the scope prototype.
	 * Singleton components should rather be injected by some type of Dependency Injection.
	 *
	 * You must use either Dependency Injection or this factory method for instantiation
	 * of your objects if you need FLOW3's object management capabilities (including
	 * Aspect Oriented Programming). It is absolutely okay and often advisable to
	 * use the "new" operator for instantiation in your automated tests.
	 *
	 * Note: If neccessary (while using legacy classes for example), you may
	 *       pass additional parameters which are then used as parameters passed
	 *       to the constructor of the component class. However, you whould only
	 *       use this feature if your parameters are truly dynamic. Otherwise just
	 *       configure them in your Components.php file.
	 *
	 * @param string $componentName The name of the component to return an instance of
	 * @return object The component instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_Component_Exception_UnknownComponent if a component with the given name does not exist
	 */
	public function getComponent($componentName) {
		if (!$this->componentManager->isComponentRegistered($componentName)) throw new F3_FLOW3_Component_Exception_UnknownComponent('Component "' . $componentName . '" is not registered.', 1166550023);

		$componentConfiguration = $this->componentManager->getComponentConfiguration($componentName);
		$arguments = array_slice(func_get_args(), 1);
		$overridingConstructorArguments = $this->getOverridingConstructorArguments($arguments);
		switch ($componentConfiguration->getScope()) {
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
				throw new F3_FLOW3_Component_Exception('Support for scope "' . $componentConfiguration->getScope() . '" has not been implemented (yet)', 1167484148);
		}

		return $componentObject;
	}

	/**
	 * Returns straight-value constructor arguments for a component by creating appropriate
	 * F3_FLOW3_Component_ConfigurationArgument objects.
	 *
	 * @param array $arguments: Array of argument values. Index must start at "0" for parameter "1" etc.
	 * @return array An array of F3_FLOW3_Component_ConfigurationArgument which can be passed to the object builder
	 * @author Robert Lemke <robert@typo3.org>
	 * @see getComponent()
	 */
	protected function getOverridingConstructorArguments(array $arguments) {
		$constructorArguments = array();
		foreach ($arguments as $index => $value) {
			$constructorArguments[$index + 1] = new F3_FLOW3_Component_ConfigurationArgument($index + 1, $value, F3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		}
		return $constructorArguments;
	}

	/**
	 * Controls cloning of the component factory. Cloning should only be used within unit tests.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		$this->componentObjectCache = clone $this->componentObjectCache;
		$this->componentObjectBuilder = clone $this->componentObjectBuilder;
	}
}
?>