<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Container;

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
 * Additional interface for a static object container
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface StaticObjectContainerInterface extends \F3\FLOW3\Object\Container\ObjectContainerInterface {

	/**
	 * Initializes the session and loads all existing instances of scope session.
	 *
	 * @return void
	 */
	public function initializeSession();

	/**
	 * Imports object instances and shutdown objects from a Dynamic Container
	 *
	 * @param \F3\FLOW3\Object\Container\DynamicObjectContainer
	 * @return void
	 */
	public function import(\F3\FLOW3\Object\Container\DynamicObjectContainer $dynamicObjectContainer);
	
	/**
	 * Shuts down this Object Container by calling the shutdown methods of all
	 * object instances which were configured to be shut down.
	 *
	 * @return void
	 */
	public function shutdown();
	
}
?>