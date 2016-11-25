<?php
namespace Neos\Flow\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\ApplicationContext;

/**
 * Interface for the Flow Object Manager
 *
 * @api
 */
interface ObjectManagerInterface
{
    const INITIALIZATIONCAUSE_CREATED = 1;
    const INITIALIZATIONCAUSE_RECREATED = 2;

    /**
     * Returns the currently set context.
     *
     * @return ApplicationContext the current context
     * @api
     */
    public function getContext();

    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * Important:
     *
     * If possible, instances of Prototype objects should always be created with the
     * new keyword and Singleton objects should rather be injected by some type of
     * Dependency Injection.
     *
     * @param string $objectName The name of the object to return an instance of
     * @return object The object instance
     * @api
     */
    public function get($objectName);

    /**
     * Returns TRUE if an object with the given name has already
     * been registered.
     *
     * @param  string $objectName Name of the object
     * @return boolean TRUE if the object has been registered, otherwise FALSE
     * @since 1.0.0 alpha 8
     * @api
     */
    public function isRegistered($objectName);

    /**
     * Registers the passed shutdown lifecycle method for the given object
     *
     * @param object $object The object to register the shutdown method for
     * @param string $shutdownLifecycleMethodName The method name of the shutdown method to be called
     * @return void
     * @api
     */
    public function registerShutdownObject($object, $shutdownLifecycleMethodName);

    /**
     * Returns the case sensitive object name of an object specified by a
     * case insensitive object name. If no object of that name exists,
     * FALSE is returned.
     *
     * In general, the case sensitive variant is used everywhere in Flow,
     * however there might be special situations in which the
     * case sensitive name is not available. This method helps you in these
     * rare cases.
     *
     * @param  string $caseInsensitiveObjectName The object name in lower-, upper- or mixed case
     * @return mixed Either the mixed case object name or FALSE if no object of that name was found.
     * @api
     */
    public function getCaseSensitiveObjectName($caseInsensitiveObjectName);

    /**
     * Returns the object name corresponding to a given class name.
     *
     * @param string $className The class name
     * @return string The object name corresponding to the given class name
     * @api
     */
    public function getObjectNameByClassName($className);

    /**
     * Returns the implementation class name for the specified object
     *
     * @param string $objectName The object name
     * @return string The class name corresponding to the given object name or FALSE if no such object is registered
     * @api
     */
    public function getClassNameByObjectName($objectName);

    /**
     * Returns the key of the package the specified object is contained in.
     *
     * @param string $objectName The object name
     * @return string The package key or FALSE if no such object exists
     */
    public function getPackageKeyByObjectName($objectName);

    /**
     * Returns the scope of the specified object.
     *
     * @param string $objectName The object name
     * @return integer One of the Configuration::SCOPE_ constants
     */
    public function getScope($objectName);

    /**
     * Sets the instance of the given object
     *
     * @param string $objectName The object name
     * @param object $instance A prebuilt instance
     * @return void
     */
    public function setInstance($objectName, $instance);

    /**
     * Unsets the instance of the given object
     *
     * If run during standard runtime, the whole application might become unstable
     * because certain parts might already use an instance of this object. Therefore
     * this method should only be used in a setUp() method of a functional test case.
     *
     * @param string $objectName The object name
     * @return void
     */
    public function forgetInstance($objectName);

    /**
     * Returns all instances of objects with scope session
     *
     * @return array
     */
    public function getSessionInstances();

    /**
     * Shuts the object manager down and calls the shutdown methods of all objects
     * which are configured for it.
     *
     * @return void
     */
    public function shutdown();
}
