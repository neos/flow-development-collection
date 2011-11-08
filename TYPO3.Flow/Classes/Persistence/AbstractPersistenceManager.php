<?php
namespace TYPO3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The FLOW3 Persistence Manager base class
 *
 * @api
 */
abstract class AbstractPersistenceManager implements \TYPO3\FLOW3\Persistence\PersistenceManagerInterface {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var array
	 */
	protected $newObjects = array();

	/**
	 * Injects the FLOW3 settings, the persistence part is kept
	 * for further use.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings['persistence'];
	}

	/**
	 * Clears the in-memory state of the persistence.
	 *
	 * @return void
	 */
	public function clearState() {
		$this->newObjects = array();
	}

	/**
	 * Registers an object which has been created or cloned during this request.
	 *
	 * The given object must contain the FLOW3_Persistence_Identifier property, thus
	 * the PersistenceMagicInterface type hint. A "new" object does not necessarily
	 * have to be known by any repository or be persisted in the end.
	 *
	 * Objects registered with this method must be known to the getObjectByIdentifier()
	 * method.
	 *
	 * @param \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicInterface $object The new object to register
	 * @return void
	 */
	public function registerNewObject(\TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicInterface $object) {
		$identifier = \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($object, 'FLOW3_Persistence_Identifier', TRUE);
		$this->newObjects[$identifier] = $object;
	}

	/**
	 * Converts the given object into an array containing the identity of the domain object.
	 *
	 * @param object $object The object to be converted
	 * @return array The identity array in the format array('__identity' => '...')
	 * @throws \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException if the given object is not known to the Persistence Manager
	 */
	public function convertObjectToIdentityArray($object) {
		$identifier = $this->getIdentifierByObject($object);
		if ($identifier === NULL) {
			throw new \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException('The given object is unknown to the Persistence Manager.', 1302628242);
		}
		return array('__identity' => $identifier);
	}

	/**
	 * Recursively iterates through the given array and turns objects
	 * into an arrays containing the identity of the domain object.
	 *
	 * @param array $array The array to be iterated over
	 * @return array The modified array without objects
	 * @throws \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException if array contains objects that are not known to the Persistence Manager
	 */
	public function convertObjectsToIdentityArrays(array $array) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = $this->convertObjectsToIdentityArrays($value);
			} elseif (is_object($value) && $value instanceof \Traversable) {
				$array[$key] = $this->convertObjectsToIdentityArrays(iterator_to_array($value));
			} elseif (is_object($value)) {
				$array[$key] = $this->convertObjectToIdentityArray($value);
			}
		}
		return $array;
	}
}

?>