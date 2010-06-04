<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * The Object ObjectFactory is mainly used for creating non-singleton objects (ie. with the
 * scope prototype).
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @deprecated since 1.0.0 alpha 8
 */
class ObjectFactory implements \F3\FLOW3\Object\ObjectFactoryInterface {

	/**
	 * A reference to the object manager
	 *
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * This method is deprecated, use the Object Manager's create() method instead.
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @deprecated since 1.0.0 alpha 8
	 */
	public function create($objectName) {
		return call_user_func_array(array($this->objectManager, 'create'), func_get_args());
	}
}
?>