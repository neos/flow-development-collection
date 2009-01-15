<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * Testcase for the MVC Abstract Controller
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectSetsCurrentPackage() {
		$package = new \F3\FLOW3\Package\Package('FLOW3', __DIR__ . '/../../');
		$packageKey = uniqid('Test');
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array('getPackage'), array(), '', FALSE);
		$mockPackageManager->expects($this->atLeastOnce())->method('getPackage')->will($this->returnValue($package));

		$controller = $this->getMock('F3\FLOW3\MVC\Controller\AbstractController', array(), array($this->getMock('F3\FLOW3\Object\FactoryInterface'), $mockPackageManager), 'F3\\' . $packageKey . '\Controller', TRUE);

		$this->assertEquals($packageKey, $this->readAttribute($controller, 'packageKey'), 'The package key is not as expected.');
		$this->assertEquals($package, $this->readAttribute($controller, 'package'), 'The package is not the one we injected.');
	}
}
?>