<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence;

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
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * The persistence session - acts as a Unit of Work for FLOW3's persistence framework.
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @prototype
 */
class Session {

	/**
	 * New objects
	 *
	 * @var array
	 */
	protected $newObjects = array();

	/**
	 * Registers a newly instantiated object
	 *
	 * @param object The object to register
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerNewObject($object) {
		$this->newObjects[spl_object_hash($object)] = $object;
	}

	/**
	 * States if the given object is registered as a new object
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isNew($object) {
		return isset($this->newObjects[spl_object_hash($object)]);
	}

	/**
	 * Returns all objects which have been registered as new objects
	 *
	 * @return array All new objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNewObjects() {
		return $this->newObjects;
	}


}
?>