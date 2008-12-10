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
 * Interface for the TYPO3 Object Manager
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\Manager.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface ManagerInterface {

	/**
	 * Sets the Object Manager to a specific context. All operations related to objects
	 * will be carried out based on the configuration for the current context.
	 *
	 * The context should be set as early as possible, preferably before any object has been
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
	 * Returns a reference to the object factory used by the object manager.
	 *
	 * @return \F3\FLOW3\Object\FactoryInterface
	 */
	public function getObjectFactory();

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * Important:
	 * 
	 * If possible, instances of Prototype objects should always be created with the
	 * Object Factory's create() method and Singleton objects should rather be 
	 * injected by some type of Dependency Injection.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @throws \F3\FLOW3\Object\Exception\UnknownObject if an object with the given name does not exist
	 */
	public function getObject($objectName);

	/**
	 * Registers the given class as an object
	 *
	 * @param string $objectName: The unique identifier of the object
	 * @param string $className: The class name which provides the functionality for this object. Same as object name by default.
	 * @return void
	 */
	public function registerObject($objectName, $className = NULL);

	/**
	 * Unregisters the specified object
	 *
	 * @param string $objectName: The explicit object name
	 * @return void
	 */
	public function unregisterObject($objectName);

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param string $objectName: Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 */
	public function isObjectRegistered($objectName);

	/**
	 * Returns the case sensitive object name of an object specified by a
	 * case insensitive object name. If no object of that name exists,
	 * FALSE is returned.
	 *
	 * In general, the case sensitive variant is used everywhere in the TYPO3
	 * framework, however there might be special situations in which the
	 * case senstivie name is not available.
	 *
	 * @param string $caseInsensitiveObjectName: The object name in lower-, upper- or mixed case
	 * @return mixed Either the mixed case object name or FALSE if no object of that name was found.
	 */
	public function getCaseSensitiveObjectName($caseInsensitiveObjectName);

	/**
	 * Returns an array of configuration objects for all registered objects.
	 *
	 * @return arrray Array of \F3\FLOW3\Object\Configuration objects, indexed by object name
	 */
	public function getObjectConfigurations();

	/**
	 * Returns the configuration object of a certain object
	 *
	 * @param string $objectName: Name of the object to fetch the configuration for
	 * @return \F3\FLOW3\Object\Configuration The object configuration
	 */
	public function getObjectConfiguration($objectName);

	/**
	 * Sets the object configurations for all objects found in the
	 * $newObjectConfigurations array.
	 *
	 * NOTE: Only objects which have been registered previously can be
	 *       configured. Trying to configure an unregistered object will
	 *       result in an exception thrown.
	 *
	 * @param array $newObjectConfigurations: Array of $objectName => \F3\FLOW3\Object::configuration
	 * @return void
	 */
	public function setObjectConfigurations(array $newObjectConfigurations);

	/**
	 * Sets the object configuration for a specific object
	 *
	 * NOTE: Only objects which have been registered previously can be
	 *       configured. Trying to configure an unregistered object will
	 *       result in an exception thrown.
	 *
	 * @param \F3\FLOW3\Object\Configuration $newObjectConfiguration: The new object configuration
	 * @return void
	 */
	public function setObjectConfiguration(\F3\FLOW3\Object\Configuration $newObjectConfiguration);

	/**
	 * Sets the name of the class implementing the specified object.
	 * This is a convenience method which loads the configuration of the given
	 * object, sets the class name and saves the configuration again.
	 *
	 * @param string $objectName: Name of the object to set the class name for
	 * @param string $className: Name of the class to set
	 * @return void
	 */
	public function setObjectClassName($objectName, $className);
}

?>