<?php
namespace TYPO3\Flow\Persistence;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Flow Persistence Manager interface
 *
 * @api
 */
interface PersistenceManagerInterface {

	/**
	 * Injects the Flow settings, called by Flow.
	 *
	 * @param array $settings
	 * @return void
	 * @api
	 */
	public function injectSettings(array $settings);

	/**
	 * Initializes the persistence manager, called by Flow.
	 *
	 * @return void
	 * @api
	 */
	public function initialize();

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll();

	/**
	 * Clears the in-memory state of the persistence.
	 *
	 * Managed instances become detached, any fetches will
	 * return data directly from the persistence "backend".
	 *
	 * @return void
	 */
	public function clearState();

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 * @api
	 */
	public function isNewObject($object);

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
	 * @param \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface $object The new object to register
	 * @return void
	 */
	public function registerNewObject(\TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface $object);

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
	 * @param string $objectType
	 * @param boolean $useLazyLoading Set to TRUE if you want to use lazy loading for this object
	 * @return object The object for the identifier if it is known, or NULL
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE);

	/**
	 * Converts the given object into an array containing the identity of the domain object.
	 *
	 * @param object $object The object to be converted
	 * @return array The identity array in the format array('__identity' => '...')
	 * @throws \TYPO3\Flow\Persistence\Exception\UnknownObjectException if the given object is not known to the Persistence Manager
	 * @api
	 */
	public function convertObjectToIdentityArray($object);

	/**
	 * Recursively iterates through the given array and turns objects
	 * into arrays containing the identity of the domain object.
	 *
	 * @param array $array The array to be iterated over
	 * @return array The modified array without objects
	 * @throws \TYPO3\Flow\Persistence\Exception\UnknownObjectException if array contains objects that are not known to the Persistence Manager
	 * @api
	 * @see convertObjectToIdentityArray()
	 */
	public function convertObjectsToIdentityArrays(array $array);

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @api
	 */
	public function createQueryForType($type);

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object);

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object);

	/**
	 * Update an object in the persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\UnknownObjectException
	 * @api
	 */
	public function update($object);

	/**
	 * Returns TRUE, if an active connection to the persistence
	 * backend has been established, e.g. entities can be persisted.
	 *
	 * @return boolean TRUE, if an connection has been established, FALSE if add object will not be persisted by the backend
	 * @api
	 */
	public function isConnected();

}
