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
 * Interface for the TYPO3 Component Manager
 *
 * @package FLOW3
 * @subpackage Component
 * @version $Id:F3_FLOW3_Component_Manager.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Component_ManagerInterface {

	/**
	 * Sets the Component Manager to a specific context. All operations related to components
	 * will be carried out based on the configuration for the current context.
	 *
	 * The context should be set as early as possible, preferably before any component has been
	 * instantiated.
	 *
	 * By default the context is set to "default". Although the context can be freely chosen,
	 * the following contexts are explicitly supported by FLOW3:
	 * "default", "production", "development", "testing", "profiling"
	 *
	 * @param string $context: Name of the context
	 * @return void
	 */
	public function setContext($context);

	/**
	 * Returns the name of the currently set context.
	 *
	 * @return string Name of the current context
	 */
	public function getContext();


	/**
	 * Returns a reference to the component factory used by the component manager.
	 *
	 * @return F3_FLOW3_Component_FactoryInterface
	 */
	public function getComponentFactory();

	/**
	 * Registers the given class as a component
	 *
	 * @param string $componentName: The unique identifier of the component
	 * @param string $className: The class name which provides the functionality for this component. Same as component name by default.
	 * @return void
	 */
	public function registerComponent($componentName, $className = NULL);

	/**
	 * Unregisters the specified component
	 *
	 * @param string $componentName: The explicit component name
	 * @return void
	 */
	public function unregisterComponent($componentName);

	/**
	 * Returns TRUE if a component with the given name has already
	 * been registered.
	 *
	 * @param string $componentName: Name of the component
	 * @return boolean TRUE if the component has been registered, otherwise FALSE
	 */
	public function isComponentRegistered($componentName);

	/**
	 * Returns the case sensitive component name of a component specified by a
	 * case insensitive component name. If no component of that name exists,
	 * FALSE is returned.
	 *
	 * In general, the case sensitive variant is used everywhere in the TYPO3
	 * framework, however there might be special situations in which the
	 * case senstivie name is not available.
	 *
	 * @param string $caseInsensitiveComponentName: The component name in lower-, upper- or mixed case
	 * @return mixed Either the mixed case component name or FALSE if no component of that name was found.
	 */
	public function getCaseSensitiveComponentName($caseInsensitiveComponentName);

	/**
	 * Returns an array of configuration objects for all registered components.
	 *
	 * @return arrray Array of F3_FLOW3_Component_Configuration objects, indexed by component name
	 */
	public function getComponentConfigurations();

	/**
	 * Returns the configuration object of a certain component
	 *
	 * @param string $componentName: Name of the component to fetch the configuration for
	 * @return F3_FLOW3_Component_Configuration The component configuration
	 */
	public function getComponentConfiguration($componentName);

	/**
	 * Sets the component configurations for all components found in the
	 * $newComponentConfigurations array.
	 *
	 * NOTE: Only components which have been registered previously can be
	 *       configured. Trying to configure an unregistered component will
	 *       result in an exception thrown.
	 *
	 * @param array $newComponentConfigurations: Array of $componentName => F3_FLOW3_Component_configuration
	 * @return void
	 */
	public function setComponentConfigurations(array $newComponentConfigurations);

	/**
	 * Sets the component configuration for a specific component
	 *
	 * NOTE: Only components which have been registered previously can be
	 *       configured. Trying to configure an unregistered component will
	 *       result in an exception thrown.
	 *
	 * @param F3_FLOW3_Component_Configuration $newComponentConfiguration: The new component configuration
	 * @return void
	 */
	public function setComponentConfiguration(F3_FLOW3_Component_Configuration $newComponentConfiguration);

	/**
	 * Sets the name of the class implementing the specified component.
	 * This is a convenience method which loads the configuration of the given
	 * component, sets the class name and saves the configuration again.
	 *
	 * @param string $componentName: Name of the component to set the class name for
	 * @param string $className: Name of the class to set
	 * @return void
	 */
	public function setComponentClassName($componentName, $className);
}

?>