<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class LazySplObjectStorage extends \SplObjectStorage {

	/**
	 * The identifiers of the objects contained in the \SplObjectStorage
	 * @var array
	 */
	protected $objectIdentifiers = array();

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @param \F3\FLOW3\Persistence\PersistenceManager $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceManager($persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @param array $objectIdentifiers
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(array $objectIdentifiers) {
		$this->objectIdentifiers = $objectIdentifiers;
	}

	/**
	 * Loads the objects this LazySplObjectStorage is supposed to hold.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initialize() {
		if (is_array($this->objectIdentifiers)) {
			foreach ($this->objectIdentifiers as $identifier) {
				$object = $this->persistenceManager->getObjectByIdentifier($identifier);
					// when security query rewriting holds back an object here, we skip it...
				if ($object !== NULL) {
					parent::attach($object);
				}
			}
			$this->objectIdentifiers = NULL;
		}
	}

	/**
	 * Returns TRUE if the LazySplObjectStorage has been initialized.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
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