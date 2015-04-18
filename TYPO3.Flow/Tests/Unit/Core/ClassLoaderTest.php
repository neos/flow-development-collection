<?php
namespace TYPO3\Flow\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Core\ClassLoader;
use TYPO3\Flow\Package\Package;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the object class loader
 *
 */
class ClassLoaderTest extends UnitTestCase {

	/**
	 * @var ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var Package|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPackage1;

	/**
	 * @var Package|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPackage2;

	/**
	 * @var Package[]|\PHPUnit_Framework_MockObject_MockObject[]
	 */
	protected $mockPackages;

	/**
	 * Test flag used in this test case
	 *
	 * @var boolean
	 */
	public static $testClassWasLoaded = FALSE;

	/**
	 * Test flag used in this test case
	 *
	 * @var boolean
	 */
	public static $testClassWasOverwritten;

	/**
	 */
	public function setUp() {
		vfsStream::setup('Test');

		self::$testClassWasLoaded = FALSE;

		$this->classLoader = new ClassLoader();

		$this->mockPackage1 = $this->getMockBuilder('TYPO3\Flow\Package\Package')->disableOriginalConstructor()->getMock();
		$this->mockPackage1->expects($this->any())->method('getNamespace')->will($this->returnValue('Acme\\MyApp'));
		$this->mockPackage1->expects($this->any())->method('getClassesPath')->will($this->returnValue('vfs://Test/Packages/Application/Acme.MyApp/Classes/'));
		$this->mockPackage1->expects($this->any())->method('getPackagePath')->will($this->returnValue('vfs://Test/Packages/Application/Acme.MyApp/'));

		$this->mockPackage2 = $this->getMockBuilder('TYPO3\Flow\Package\Package')->disableOriginalConstructor()->getMock();
		$this->mockPackage2->expects($this->any())->method('getNamespace')->will($this->returnValue('Acme\\MyAppAddon'));
		$this->mockPackage2->expects($this->any())->method('getClassesPath')->will($this->returnValue('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/'));
		$this->mockPackage2->expects($this->any())->method('getPackagePath')->will($this->returnValue('vfs://Test/Packages/Application/Acme.MyAppAddon/'));

		$this->mockPackages = array('Acme.MyApp' => $this->mockPackage1, 'Acme.MyAppAddon' => $this->mockPackage2);

		$this->classLoader->setPackages($this->mockPackages , $this->mockPackages );
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function classesFromSubDirectoriesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/ClassInSubDirectory.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

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

		$this->classLoader->setConsiderTestsNamespace(TRUE);
		$this->classLoader->setPackages($this->mockPackages , $this->mockPackages );
		$this->classLoader->loadClass('Acme\MyApp\Tests\Functional\Essentials\LawnMowerTest');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesFromDeeplyNestedSubDirectoriesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/A/B/C/D', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/A/B/C/D/E.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->loadClass('Acme\MyApp\SubDirectory\A\B\C\D\E');
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

		$this->classLoader->loadClass('Acme\MyAppAddon\Class');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function classesWithUnderscoresAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->loadClass('Acme\MyApp_Foo');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories with underscores.
	 *
	 * @test
	 */
	public function namespaceWithUnderscoresAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/My_Underscore', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/My_Underscore/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->loadClass('Acme\MyApp\My_Underscore\Foo');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function classesWithOnlyUnderscoresAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->loadClass('Acme_MyApp_Foo');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesWithLeadingBackslashAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->loadClass('\Acme\MyApp\Foo');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesFromInactivePackagesAreNotLoaded() {
		$this->classLoader = new ClassLoader();
		$allPackages = array('Acme.MyApp' => $this->mockPackage1, 'Acme.MyAppAddon' => $this->mockPackage2);
		$activePackages = array('Acme.MyApp' => $this->mockPackage1);
		$this->classLoader->setPackages($allPackages, $activePackages);
		mkdir('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon/Class.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->loadClass('Acme\MyAppAddon\Class');
		$this->assertFalse(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesFromPsr4PackagesAreLoaded() {
		$this->mockPackage1->expects($this->any())->method('getAutoloadType')->will($this->returnValue(Package::AUTOLOADER_TYPE_PSR4));

		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->setPackages($this->mockPackages , $this->mockPackages );
		$this->classLoader->loadClass('Acme\MyApp\Foo');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesFromOverlayedPsr4PackagesAreLoaded() {
		$this->classLoader = new ClassLoader();

		$mockPackage1 = $this->getMockBuilder('TYPO3\Flow\Package\Package')->disableOriginalConstructor()->getMock();
		$mockPackage1->expects($this->any())->method('getAutoloadType')->will($this->returnValue(Package::AUTOLOADER_TYPE_PSR4));
		$mockPackage1->expects($this->any())->method('getNamespace')->will($this->returnValue('TestPackage\\Subscriber\\Log'));
		$mockPackage1->expects($this->any())->method('getClassesPath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/subPackage/src/'));
		$mockPackage1->expects($this->any())->method('getPackagePath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/subPackage/src/'));

		$mockPackage2 = $this->getMockBuilder('TYPO3\Flow\Package\Package')->disableOriginalConstructor()->getMock();
		$mockPackage2->expects($this->any())->method('getAutoloadType')->will($this->returnValue(Package::AUTOLOADER_TYPE_PSR4));
		$mockPackage2->expects($this->any())->method('getNamespace')->will($this->returnValue('TestPackage'));
		$mockPackage2->expects($this->any())->method('getClassesPath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/mainPackage/src/'));
		$mockPackage2->expects($this->any())->method('getPackagePath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/mainPackage/src/'));

		$packages = array($mockPackage2, $mockPackage1);
		mkdir('vfs://Test/Packages/Libraries/test/subPackage/src/', 0770, TRUE);
		mkdir('vfs://Test/Packages/Libraries/test/mainPackage/src/Subscriber', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Libraries/test/subPackage/src/Bar.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');
		file_put_contents('vfs://Test/Packages/Libraries/test/mainPackage/src/Subscriber/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		$this->classLoader->setPackages($packages, $packages);


		$this->classLoader->loadClass('TestPackage\Subscriber\Foo');
		$this->assertTrue(self::$testClassWasLoaded);

		self::$testClassWasLoaded = FALSE;

		$this->classLoader->loadClass('TestPackage\Subscriber\Log\Bar');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesFromOverlayedPsr4PackagesAreOverwritten() {
		$this->classLoader = new ClassLoader();

		$mockPackage1 = $this->getMockBuilder('TYPO3\Flow\Package\Package')->disableOriginalConstructor()->getMock();
		$mockPackage1->expects($this->any())->method('getAutoloadType')->will($this->returnValue(Package::AUTOLOADER_TYPE_PSR4));
		$mockPackage1->expects($this->any())->method('getNamespace')->will($this->returnValue('TestPackage\\Foo'));
		$mockPackage1->expects($this->any())->method('getClassesPath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/subPackage/src/'));
		$mockPackage1->expects($this->any())->method('getPackagePath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/subPackage/src/'));

		$mockPackage2 = $this->getMockBuilder('TYPO3\Flow\Package\Package')->disableOriginalConstructor()->getMock();
		$mockPackage2->expects($this->any())->method('getAutoloadType')->will($this->returnValue(Package::AUTOLOADER_TYPE_PSR4));
		$mockPackage2->expects($this->any())->method('getNamespace')->will($this->returnValue('TestPackage'));
		$mockPackage2->expects($this->any())->method('getClassesPath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/mainPackage/src/'));
		$mockPackage2->expects($this->any())->method('getPackagePath')->will($this->returnValue('vfs://Test/Packages/Libraries/test/mainPackage/src/'));

		$packages = array($mockPackage2, $mockPackage1);
		mkdir('vfs://Test/Packages/Libraries/test/subPackage/src/', 0770, TRUE);
		mkdir('vfs://Test/Packages/Libraries/test/mainPackage/src/Foo', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Libraries/test/subPackage/src/Bar.php', '<?php ' . __CLASS__ . '::$testClassWasOverwritten = TRUE; ?>');
		file_put_contents('vfs://Test/Packages/Libraries/test/mainPackage/src/Foo/Bar.php', '<?php ' . __CLASS__ . '::$testClassWasOverwritten = FALSE; ?>');

		$this->classLoader->setPackages($packages, $packages);


		$this->classLoader->loadClass('TestPackage\Foo\Bar');
		$this->assertTrue(self::$testClassWasOverwritten);
	}
}
