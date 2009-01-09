<?php
declare(ENCODING = 'utf-8');
namespace F3;

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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the FLOW3 base class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class FLOW3Test extends \F3\Testing\BaseTestCase {

	/**
	 * Checks the method getObjectManager() and the magic getter
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectManagerReturnsObjectManager() {
		$FLOW3 = new \F3\FLOW3;
		$FLOW3->initializeClassLoader();
		$FLOW3->initializeConfiguration();
		$FLOW3->initializeError();
		$FLOW3->initializeObjectFramework();
		$this->assertTrue($FLOW3->getObjectManager() instanceof \F3\FLOW3\Object\ManagerInterface, 'getObjectManager did not deliver an object implementing \F3\FLOW3\Object\ManagerInterface!');
	}
}
?>