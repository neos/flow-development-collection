<?php
namespace TYPO3\Flow\Persistence\Generic;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or(at your *
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
 * A lazy loading variant of \SplObjectStorage
 *
 * @api
 */
class LazySplObjectStorage extends \SplObjectStorage {

	/**
	 * The identifiers of the objects contained in the \SplObjectStorage
	 * @var array
	 */
	protected $objectIdentifiers = array();

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @param \TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @param array $objectIdentifiers
	 */
	public function __construct(array $objectIdentifiers) {
		$this->objectIdentifiers = $objectIdentifiers;
	}

	/**
	 * Loads the objects this LazySplObjectStorage is supposed to hold.
	 *
	 * @return void
	 */
	protected function initialize() {
		if (is_array($this->objectIdentifiers)) {
			foreach ($this->objectIdentifiers as $identifier) {
				try {
					parent::attach($this->persistenceManager->getObjectByIdentifier($identifier));
				} catch (\TYPO3\Flow\Persistence\Generic\Exception\InvalidObjectDataException $e) {
					// when security query rewriting holds back an object here, we skip it...
				}
			}
			$this->objectIdentifiers = NULL;
		}
	}

	/**
	 * Returns TRUE if the LazySplObjectStorage has been initialized.
	 *
	 * @return boolean
	 */
	public function isInitialized() {
		return !is_array($this->objectIdentifiers);
	}


	// Standard SplObjectStorage methods below


	public function addAll($storage) {
		$this->initialize();
		parent::addAll($storage);
	}

	public function attach($object, $data = NULL) {
		$this->initialize();
		parent::attach($object, $data);
	}

	public function contains($object) {
		$this->initialize();
		return parent::contains($object);
	}

	public function count() {
		if (is_array($this->objectIdentifiers)) {
			return count($this->objectIdentifiers);
		} else {
			return parent::count();
		}
	}

	public function current() {
		$this->initialize();
		return parent::current();
	}

	public function detach($object) {
		$this->initialize();
		parent::detach($object);
	}

	public function getInfo() {
		$this->initialize();
		return parent::getInfo();
	}

	public function key() {
		$this->initialize();
		return parent::key();
	}

	public function next() {
		$this->initialize();
		parent::next();
	}

	public function offsetExists($object) {
		$this->initialize();
		return parent::offsetExists($object);
	}

	public function offsetGet($object) {
		$this->initialize();
		return parent::offsetGet($object);
	}

	public function offsetSet($object , $info) {
		$this->initialize();
		parent::offsetSet($object, $info);
	}

	public function offsetUnset($object) {
		$this->initialize();
		parent::offsetUnset($object);
	}

	public function removeAll($storage) {
		$this->initialize();
		parent::removeAll($storage);
	}

	public function rewind() {
		$this->initialize();
		parent::rewind();
	}

	public function setInfo($data) {
		$this->initialize();
		parent::setInfo($data);
	}
	public function valid() {
		$this->initialize();
		return parent::valid();
	}


	// we don't do those (yet)


	public function serialize() {
		throw new \RuntimeException('A LazyLoadingSplObjectStorage instance cannot be serialized.', 1267700868);
	}

	public function unserialize($serialized) {
		throw new \RuntimeException('A LazyLoadingSplObjectStorage instance cannot be unserialized.', 1267700870);
	}


}

?>