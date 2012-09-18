<?php
namespace TYPO3\FLOW3\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the object class loader
 *
 */
class ClassLoaderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * Test flag used in in this test case
	 *
	 * @var boolean
	 */
	public static $testClassWasLoaded = FALSE;

	/**
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Test'));

		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/', 0770, TRUE);
		$package1 = new \TYPO3\FLOW3\Package\Package('Acme.MyApp', 'vfs://Test/Packages/Application/Acme.MyApp/', 'Classes');
		mkdir('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/', 0770, TRUE);
		$package2 = new \TYPO3\FLOW3\Package\Package('Acme.MyAppAddon', 'vfs://Test/Packages/Application/Acme.MyAppAddon/', 'Classes');

		$this->classLoader = new \TYPO3\FLOW3\Core\ClassLoader();
		$this->inject($this->classLoader, 'packagesPath', 'vfs://Test/Packages/');
		$this->classLoader->setPackages(array('Acme.MyApp' => $package1, 'Acme.MyAppAddon' => $package2));
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function classesFromSubDirectoriesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/ClassInSubDirectory.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyApp\SubDirectory\ClassInSubDirectory');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the class loader loads classes from the functional tests directory
	 *
	 * @test
	 */
	public function classesFromFunctionalTestsDirectoriesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Tests/Functional/Essentials', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Tests/Functional/Essentials/LawnMowerTest.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');
		self::$testClassWasLoaded = FALSE;
		$this->classLoader->setConsiderTestsNamespace(TRUE);
		$this->classLoader->loadClass('Acme\MyApp\Tests\Functional\Essentials\LawnMowerTest');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesFromVeryDeeplyNestedSubDirectoriesAreLoaded() {
		$this->markTestSkipped('Currently, this test is unbelievably slow, and CPU is increasing radically... It seems something weird happens inside of vfsStream.');

		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/SubDirectory/A/B/C/D/E/F/G/H/I/J', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/SubDirectory/A/B/C/D/E/F/G/H/I/J/K.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyApp\SubDirectory\A\B\C\D\E\F\G\H\I\J\K');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from packages that match a
	 * substring of another package (e.g. TYPO3CR vs TYPO3).
	 *
	 * @test
	 */
	public function classesFromSubMatchingPackagesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon/Class.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyAppAddon\Class');
		$this->assertTrue(self::$testClassWasLoaded);
	}

}
?>