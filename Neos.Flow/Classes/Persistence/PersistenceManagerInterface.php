<?php
namespace Neos\Flow\Persistence;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\Exception\UnknownObjectException;

/**
 * The Flow Persistence Manager interface
 *
 * @api
 */
interface PersistenceManagerInterface
{
    /**
     * Injects the Flow settings, called by Flow.
     *
     * @param array $settings
     * @return void
     * @api
     */
    public function injectSettings(array $settings): void;

    /**
     * Commits new objects and changes to objects in the current persistence session into the backend.
     *
     * If $onlyAllowedObjects is set to true, only those objects which have been registered with
     * allowObject() will be persisted. If other objects are in the queue, an exception will be
     * raised.
     *
     * @param boolean $onlyAllowedObjects
     * @return void
     * @api
     */
    public function persistAll(bool $onlyAllowedObjects = false): void;

    /**
     * Clears the in-memory state of the persistence.
     *
     * Managed instances become detached, any fetches will
     * return data directly from the persistence "backend".
     *
     * @return void
     */
    public function clearState(): void;

    /**
     * Checks if the given object has ever been persisted.
     *
     * @param object $object The object to check
     * @return boolean true if the object is new, false if the object exists in the repository
     * @api
     */
    public function isNewObject($object): bool;

    /**
     * Registers an object which has been created or cloned during this request.
     *
     * The given object must contain the Persistence_Object_Identifier property, thus
     * the PersistenceMagicInterface type hint. A "new" object does not necessarily
     * have to be known by any repository or be persisted in the end.
     *
     * Objects registered with this method must be known to the getObjectByIdentifier()
     * method.
     *
     * @param Aspect\PersistenceMagicInterface $object The new object to register
     * @return void
     */
    public function registerNewObject(Aspect\PersistenceMagicInterface $object): void;

    /**
     * Returns the (internal) identifier for the object, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * Note: this returns an identifier even if the object has not been
     * persisted in case of AOP-managed entities. Use isNewObject() if you need
     * to distinguish those cases.
     *
     * @param object $object
     * @return mixed The identifier for the object if it is known, or NULL
     * @api
     */
    public function getIdentifierByObject($object);

    /**
     * Returns the object with the (internal) identifier, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * @param mixed $identifier
     * @param string|null $objectType
     * @psalm-param class-string|null $objectType
     * @param boolean $useLazyLoading Set to true if you want to use lazy loading for this object
     * @return object The object for the identifier if it is known, or NULL
     * @api
     */
    public function getObjectByIdentifier($identifier, string $objectType = null, bool $useLazyLoading = false);

    /**
     * Converts the given object into an array containing the identity of the domain object.
     *
     * @param object $object The object to be converted
     * @return array The identity array in the format array('__identity' => '...')
     * @throws UnknownObjectException if the given object is not known to the Persistence Manager
     * @api
     */
    public function convertObjectToIdentityArray($object): array;

    /**
     * Recursively iterates through the given array and turns objects
     * into arrays containing the identity of the domain object.
     *
     * @param array $array The array to be iterated over
     * @return array The modified array without objects
     * @throws UnknownObjectException if array contains objects that are not known to the Persistence Manager
     * @api
     * @see convertObjectToIdentityArray()
     */
    public function convertObjectsToIdentityArrays(array $array): array;

    /**
     * Return a query object for the given type.
     *
     * @param string $type
     * @return QueryInterface
     * @api
     */
    public function createQueryForType(string $type): QueryInterface;

    /**
     * Adds an object to the persistence.
     *
     * @param object $object The object to add
     * @return void
     * @api
     */
    public function add($object): void;

    /**
     * Removes an object to the persistence.
     *
     * @param object $object The object to remove
     * @return void
     * @api
     */
    public function remove($object): void;

    /**
     * Update an object in the persistence.
     *
     * @param object $object The modified object
     * @return void
     * @throws UnknownObjectException
     * @api
     */
    public function update($object): void;

    /**
     * Adds the given object to a list of allowed objects which may be persisted when persistAll() is called with the
     * $onlyAllowedObjects flag. This is the case if "safe" HTTP request methods are used.
     *
     * @param object $object The object
     * @return void
     * @api
     * @deprecated Use allowObject() instead. See https://github.com/neos/flow-development-collection/pull/2024
     */
    public function whitelistObject($object): void;

    /**
     * Adds the given object to a list of allowed objects which may be persisted when persistAll() is called with the
     * $onlyAllowedObjects flag. This is the case if "safe" HTTP request methods are used.
     *
     * @param object $object The object
     * @return void
     * @api
     */
    //public function allowObject($object): void;

    /**
     * Returns true, if an active connection to the persistence
     * backend has been established, e.g. entities can be persisted.
     *
     * @return boolean true, if an connection has been established, false if add object will not be persisted by the backend
     * @api
     */
    public function isConnected(): bool;

    /**
     * Gives feedback if the persistence Manager has unpersisted changes.
     *
     * This is primarily used to inform the user if he tries to save
     * data in an unsafe request.
     *
     * @return boolean
     */
    public function hasUnpersistedChanges(): bool;
}
