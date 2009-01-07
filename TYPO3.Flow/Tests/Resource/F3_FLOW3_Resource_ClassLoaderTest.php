<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * @version $Id:\F3\FLOW3\Object\ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the object class loader
 *
 * @package    FLOW3
 * @version    $Id:\F3\FLOW3\Object\ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ClassLoaderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Resource\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		$this->classLoader = new \F3\FLOW3\Resource\ClassLoader(\vfsStream::url('Packages/'));
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function classesFromSubDirectoriesAreLoaded() {
		$root = \vfsStream::newDirectory('Packages/TestPackage/Classes/SubDirectory');
		\vfsStreamWrapper::setRoot($root);

		$vfsClassFile = \vfsStream::newFile('F3_TestPackage_SubDirectory_ClassInSubDirectory.php')
			->withContent('<?php ?>')
			->at($root->getChild('TestPackage/Classes/SubDirectory'));

		$this->classLoader->loadClass('F3\TestPackage\SubDirectory\ClassInSubDirectory');

		$this->assertTrue($vfsClassFile->eof());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function classesFromVeryDeeplyNestedSubDirectoriesAreLoaded() {
		$root = \vfsStream::newDirectory('Packages/TestPackage/Classes/SubDirectory/A/B/C/D/E/F/G/H/I/J');
		\vfsStreamWrapper::setRoot($root);

		$vfsClassFile = \vfsStream::newFile('F3_TestPackage_SubDirectory_A_B_C_D_E_F_G_H_I_J_TheClass.php')
			->withContent('<?php ?>')
			->at($root->getChild('TestPackage/Classes/SubDirectory/A/B/C/D/E/F/G/H/I/J'));

		$this->classLoader->loadClass('F3\TestPackage\SubDirectory\A\B\C\D\E\F\G\H\I\J\TheClass');

		$this->assertTrue($vfsClassFile->eof());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function specialClassNamesAndPathsSettingsOverrideClassLoaderBehaviour() {
		$root = \vfsStream::newDirectory('Packages/TestPackage/Resources/PHP');
		\vfsStreamWrapper::setRoot($root);

		$vfsClassFile = \vfsStream::newFile('Bar.php')
			->withContent('<?php ?>')
			->at($root->getChild('TestPackage/Resources/PHP'));

		$this->classLoader->setSpecialClassNameAndPath('Baz', \vfsStream::url('TestPackage/Resources/PHP/Bar.php'));
		$this->classLoader->loadClass('Baz');

		$this->assertTrue($vfsClassFile->eof());
	}
}
?>