<?php
declare(ENCODING = 'utf-8');
namespace F3;

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
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the FLOW3 base class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FLOW3Test extends F3::Testing::BaseTestCase {

	/**
	 * Checks the method getObjectManager() and the magic getter
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectManagerReturnsObjectManager() {
		$FLOW3 = new F3::FLOW3;
		$FLOW3->initializeClassLoader();
		$FLOW3->initializeConfiguration();
		$FLOW3->initializeError();
		$FLOW3->initializeObjectFramework();
		$this->assertTrue($FLOW3->getObjectManager() instanceof F3::FLOW3::Object::ManagerInterface, 'getObjectManager did not deliver an object implementing F3::FLOW3::Object::ManagerInterface!');
	}
}
?>