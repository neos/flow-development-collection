<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Controller;

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
 * @version $Id:F3::FLOW3::Component::TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the MVC Abstract Controller
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AbstractControllerTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeComponentSetsCurrentPackage() {
		$package = new F3::FLOW3::Package::Package('FLOW3', __DIR__ . '/../../');
		$packageKey = uniqid('Test');
		$mockPackageManager = $this->getMock('F3::FLOW3::Package::Manager', array('getPackage'), array(), '', FALSE);
		$mockPackageManager->expects($this->atLeastOnce())->method('getPackage')->will($this->returnValue($package));

		$controller = $this->getMock('F3::FLOW3::MVC::Controller::AbstractController', array(), array($this->componentFactory, $mockPackageManager), 'F3::' . $packageKey . '::Controller', TRUE);
		$controllerReflection = new F3::FLOW3::Reflection::ClassReflection('F3::FLOW3::MVC::Controller::AbstractController');
		$packageKeyPropertyReflection = $controllerReflection->getProperty('packageKey');
		$packagePropertyReflection = $controllerReflection->getProperty('package');

		$this->assertEquals($packageKey, $packageKeyPropertyReflection->getValue($controller), 'The package key is not as expected.');
		$this->assertEquals($package, $packagePropertyReflection->getValue($controller), 'The package is not the one we injected.');
	}
}
?>