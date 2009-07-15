<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Aspect;

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
 * An interface used to introduce certain methods to support object persistence
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface DirtyMonitoringInterface {

	/**
	 * If the monitored object has ever been persisted
	 *
	 * @return boolean TRUE if the object is new, otherwise FALSE
	 */
	public function FLOW3_Persistence_isNew();

	/**
	 * If the specified property of the reconstituted object has been modified
	 * since it woke up.
	 *
	 * @param string $propertyName Name of the property to check
	 * @return boolean TRUE if the given property has been modified
	 */
	public function FLOW3_Persistence_isDirty($propertyName);

	/**
	 * Resets the dirty flags of properties to signal that the object is clean
	 * clean (e.g. after being persisted).
	 *
	 * @param string $propertyName Name of the property to mark clean, if NULL all will be marked clean
	 * @return void
	 */
	public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL);

	/**
	 * Introduces a clone method
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone();
}
?>