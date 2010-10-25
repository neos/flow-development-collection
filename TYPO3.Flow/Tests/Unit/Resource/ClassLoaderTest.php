<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Resource;

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
 * Testcase for the object class loader
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassLoaderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Resource\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();

		$mockPackage = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackage->expects($this->any())->method('getClassesPath')->will($this->returnValue(\vfsStream::url('Virtual/Classes/')));
		$mockPackage->expects($this->any())->method('getFunctionalTestsPath')->will($this->returnValue(\vfsStream::url('Virtual/Tests/Functional/')));
		$mockPackages = array(
			'Virtual' => $mockPackage
		);

		$this->classLoader = new \F3\FLOW3\Resource\ClassLoader();
		$this->classLoader->setPackages($mockPackages);
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function classesFromSubDirectoriesAreLoaded() {
		$root = \vfsStream::newDirectory('Packages/Virtual/Classes/SubDirectory');
		\vfsStreamWrapper::setRoot($root);

		$vfsClassFile = \vfsStream::newFile('ClassInSubDirectory.php')
			->withContent('<?php ?>')
			->at($root->getChild('Virtual/Classes/SubDirectory'));

		$this->classLoader->loadClass('F3\Virtual\SubDirectory\ClassInSubDirectory');
		$this->assertTrue($vfsClassFile->eof());
	}

	/**
	 * Checks if the class loader loads classes from the functional tests directory
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classesFromFunctionalTestsDirectoriesAreLoaded() {
		$root = \vfsStream::newDirectory('Packages/Virtual/Tests/Functional/SubDirectory');
		\vfsStreamWrapper::setRoot($root);

		$vfsClassFile = \vfsStream::newFile('ClassInSubDirectory.php')
			->withContent('<?php ?>')
			->at($root->getChild('Virtual/Tests/Functional/SubDirectory'));

		$this->classLoader->loadClass('F3\Virtual\Tests\Functional\SubDirectory\ClassInSubDirectory');
		$this->assertTrue($vfsClassFile->eof());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function classesFromVeryDeeplyNestedSubDirectoriesAreLoaded() {
		$root = \vfsStream::newDirectory('Packages/Virtual/Classes/SubDirectory/A/B/C/D/E/F/G/H/I/J');
		\vfsStreamWrapper::setRoot($root);

		$vfsClassFile = \vfsStream::newFile('TheClass.php')
			->withContent('<?php ?>')
			->at($root->getChild('Virtual/Classes/SubDirectory/A/B/C/D/E/F/G/H/I/J'));

		$this->classLoader->loadClass('F3\Virtual\SubDirectory\A\B\C\D\E\F\G\H\I\J\TheClass');

		$this->assertTrue($vfsClassFile->eof());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function specialClassNamesAndPathsSettingsOverrideClassLoaderBehaviour() {
		$root = \vfsStream::newDirectory('Packages/Virtual/Resources/PHP');
		\vfsStreamWrapper::setRoot($root);

		$vfsClassFile = \vfsStream::newFile('Bar.php')
			->withContent('<?php ?>')
			->at($root->getChild('Virtual/Resources/PHP'));

		$this->classLoader->setSpecialClassNameAndPath('Baz', \vfsStream::url('Virtual/Resources/PHP/Bar.php'));
		$this->classLoader->loadClass('Baz');

		$this->assertTrue($vfsClassFile->eof());
	}
}
?>