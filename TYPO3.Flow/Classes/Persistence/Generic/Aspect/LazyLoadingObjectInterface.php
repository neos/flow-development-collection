<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Generic\Aspect;

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
 * An interface used to introduce certain methods to support lazy loading objects
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface LazyLoadingObjectInterface {

	/**
	 * Signifies lazy loading of properties in an object
	 * @type integer
	 */
	const LAZY_PROPERTIES = 1;

	/**
	 * Signifies lazy loading of properties in a SplObjectStorage
	 * @type integer
	 */
	const LAZY_OBJECTSTORAGE = 2;

	/**
	 * Introduces an initialization method.
	 *
	 * @return void
	 */
	public function FLOW3_Persistence_LazyLoadingObject_initialize();

}
?>