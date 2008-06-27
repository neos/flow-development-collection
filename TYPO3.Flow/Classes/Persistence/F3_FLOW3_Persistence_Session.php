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
class F3_FLOW3_Persistence_Session {

	/**
	 * New components
	 *
	 * @var array
	 */
	protected $newComponents = array();

	/**
	 * Registers an object as new
	 *
	 * @param object The object to register as new
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerNewComponent($object) {
		$this->newComponents[spl_object_hash($object)] = $object;
	}

	/**
	 * Returns all objects which are flagged as being new
	 *
	 * @return array All new objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNewComponents() {
		return $this->newComponents;
	}
}
?>