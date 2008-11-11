<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Resource;

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
 * @version $Id:F3::FLOW3::Object::ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the object class loader
 *
 * @package    FLOW3
 * @version    $Id:F3::FLOW3::Object::ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ClassLoaderTest extends F3::Testing::BaseTestCase {

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classesFromSubDirectoriesAreLoaded() {
		$dummyObject = new F3::TestPackage::SubDirectory::ClassInSubDirectory;
		$this->assertTrue(class_exists('F3::TestPackage::SubDirectory::ClassInSubDirectory'), 'The class in a subdirectory has not been loaded by the package autoloader.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classesFromVeryDeeplyNestedSubDirectoriesAreLoaded() {
		$this->assertTrue(class_exists('F3::TestPackage::SubDirectory::A::B::C::D::E::F::G::H::I::J::TheClass', TRUE), 'The class in a very deep sub directory has not been loaded by the package autoloader.');
	}
}
?>