<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
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
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * The FLOW3 Persistence Manager interface
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface ManagerInterface {

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 */
	public function initialize();

	/**
	 * Returns the current persistence session
	 *
	 * @return \F3\FLOW3\Persistence\Session
	 */
	public function getSession();

	/**
	 * Returns the persistence backend
	 *
	 * @return \F3\FLOW3\Persistence\BackendInterface
	 */
	public function getBackend();

	/**
	 * Returns the class schema for the given class
	 *
	 * @param string $className
	 * @return \F3\FLOW3\Persistence\ClassSchema
	 */
	public function getClassSchema($className);

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 */
	public function persistAll();

}
?>