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
 * A persistence backend interface
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Persistence_BackendInterface {

	/**
	 * Initializes the backend
	 *
	 * @param array $classSchemata the class schemata the backend will be handling
	 * @return void
	 */
	public function initialize(array $classSchemata);

	/**
	 * Sets the new objects
	 *
	 * @param array $objects
	 * @return void
	 */
	public function setNewObjects(array $objects);

	/**
	 * Sets the updated objects
	 *
	 * @param array $objects
	 * @return void
	 */
	public function setUpdatedObjects(array $objects);

	/**
	 * Sets the deleted objects
	 *
	 * @param array $objects
	 * @return void
	 */
	public function setDeletedObjects(array $objects);

	/**
	 * Commits the current persistence session
	 *
	 * @return void
	 */
	public function commit();

}
?>