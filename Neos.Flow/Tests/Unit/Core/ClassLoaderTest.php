<?php
namespace Neos\Flow\Tests\Unit\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Flow\Core\ClassLoader;
use Neos\Flow\Package\Package;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the object class loader
 *
 */
class ClassLoaderTest extends UnitTestCase
{
    /**
     * @var ClassLoader
     */
    protected $classLoader;

    /**
     * @var Package|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPackage1;

    /**
     * @var Package|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPackage2;

    /**
     * @var Package[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    protected $mockPackages;

    /**
     * Test flag used in this test case
     *
     * @var boolean
     */
    public static $testClassWasLoaded = false;

    /**
     * Test flag used in this test case
     *
     * @var boolean
     */
    public static $testClassWasOverwritten;

    /**
     */
    protected function setUp(): void
    {
        if (FLOW_ONLY_COMPOSER_LOADER) {
            $this->markTestSkipped('Not testing if composer-only loading is requested.');
        }

        vfsStream::setup('Test');

        self::$testClassWasLoaded = false;

        $this->classLoader = new ClassLoader();

        $this->mockPackage1 = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $this->mockPackage1->expects(self::any())->method('getNamespaces')->will(self::returnValue(['Acme\\MyApp']));
        $this->mockPackage1->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Test/Packages/Application/Acme.MyApp/'));
        $this->mockPackage1->expects(self::any())->method('getFlattenedAutoloadConfiguration')->will(self::returnValue([
            [
                'namespace' => 'Acme\\MyApp',
                'classPath' => 'vfs://Test/Packages/Application/Acme.MyApp/Classes/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR0
            ]
        ]));

        $this->mockPackage2 = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $this->mockPackage2->expects(self::any())->method('getNamespaces')->will(self::returnValue(['Acme\\MyAppAddon']));
        $this->mockPackage2->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Test/Packages/Application/Acme.MyAppAddon/'));
        $this->mockPackage2->expects(self::any())->method('getFlattenedAutoloadConfiguration')->will(self::returnValue([
            [
                'namespace' => 'Acme\MyAppAddon',
                'classPath' => 'vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR0
            ]
        ]));

        $this->mockPackages = ['Acme.MyApp' => $this->mockPackage1, 'Acme.MyAppAddon' => $this->mockPackage2];

        $this->classLoader->setPackages($this->mockPackages);
    }

    /**
     * Checks if the package autoloader loads classes from subdirectories.
     *
     * @test
     */
    public function classesFromSubDirectoriesAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/ClassInSubDirectory.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('Acme\MyApp\SubDirectory\ClassInSubDirectory');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * Checks if the class loader loads classes from the functional tests directory
     *
     * @test
     */
    public function classesFromFunctionalTestsDirectoriesAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Tests/Functional/Essentials', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Tests/Functional/Essentials/LawnMowerTest.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->setConsiderTestsNamespace(true);
        $this->classLoader->setPackages($this->mockPackages);

        $this->classLoader->loadClass('Acme\MyApp\Tests\Functional\Essentials\LawnMowerTest');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * @test
     */
    public function classesFromDeeplyNestedSubDirectoriesAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/A/B/C/D', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/A/B/C/D/E.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('Acme\MyApp\SubDirectory\A\B\C\D\E');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * Checks if the package autoloader loads classes from packages that match a
     * substring of another package (e.g. Media vs. Neos).
     *
     * @test
     */
    public function classesFromSubMatchingPackagesAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon/Class.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('Acme\MyAppAddon\Class');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * Checks if the package autoloader loads classes from subdirectories.
     *
     * @test
     */
    public function classesWithUnderscoresAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('Acme\MyApp_Foo');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * Checks if the package autoloader loads classes from subdirectories with underscores.
     *
     * @test
     */
    public function namespaceWithUnderscoresAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/My_Underscore', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/My_Underscore/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('Acme\MyApp\My_Underscore\Foo');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * Checks if the package autoloader loads classes from subdirectories.
     *
     * @test
     */
    public function classesWithOnlyUnderscoresAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/Foo1.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('Acme_MyApp_Foo1');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * @test
     */
    public function classesWithLeadingBackslashAreLoaded()
    {
        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/Foo2.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('\Acme\MyApp\Foo2');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * @test
     */
    public function classesFromInactivePackagesAreNotLoaded()
    {
        $this->classLoader = new ClassLoader();
        $allPackages = ['Acme.MyApp' => $this->mockPackage1, 'Acme.MyAppAddon' => $this->mockPackage2];
        $activePackages = ['Acme.MyApp' => $this->mockPackage1];
        $this->classLoader->setPackages($activePackages);
        mkdir('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon/Class.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->loadClass('Acme\MyAppAddon\Class');
        self::assertFalse(self::$testClassWasLoaded);
    }

    /**
     * @test
     */
    public function classesFromPsr4PackagesAreLoaded()
    {
        $this->mockPackage1 = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $this->mockPackage1->expects(self::any())->method('getNamespaces')->will(self::returnValue(['Acme\\MyApp']));
        $this->mockPackage1->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Test/Packages/Application/Acme.MyApp/'));
        $this->mockPackage1->expects(self::any())->method('getFlattenedAutoloadConfiguration')->will(self::returnValue([
            [
                'namespace' => 'Acme\\MyApp',
                'classPath' => 'vfs://Test/Packages/Application/Acme.MyApp/Classes/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ]
        ]));

        mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes', 0770, true);
        file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->mockPackages['Acme.MyApp'] = $this->mockPackage1;
        $this->classLoader->setPackages($this->mockPackages);
        $this->classLoader->loadClass('Acme\MyApp\Foo');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * @test
     */
    public function classesFromOverlayedPsr4PackagesAreLoaded()
    {
        $this->classLoader = new ClassLoader();

        $mockPackage1 = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackage1->expects(self::any())->method('getNamespaces')->will(self::returnValue(['TestPackage\\Subscriber\\Log']));
        $mockPackage1->expects(self::any())->method('getFlattenedAutoloadConfiguration')->will(self::returnValue([
            [
                'namespace' => 'TestPackage\Subscriber\Log',
                'classPath' => 'vfs://Test/Packages/Libraries/test/subPackage/src/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ]
        ]));

        $mockPackage2 = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackage2->expects(self::any())->method('getFlattenedAutoloadConfiguration')->will(self::returnValue([
            [
                'namespace' => 'TestPackage',
                'classPath' => 'vfs://Test/Packages/Libraries/test/mainPackage/src/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ]
        ]));

        $packages = [$mockPackage2, $mockPackage1];
        mkdir('vfs://Test/Packages/Libraries/test/subPackage/src/', 0770, true);
        mkdir('vfs://Test/Packages/Libraries/test/mainPackage/src/Subscriber', 0770, true);
        file_put_contents('vfs://Test/Packages/Libraries/test/subPackage/src/Bar.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');
        file_put_contents('vfs://Test/Packages/Libraries/test/mainPackage/src/Subscriber/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = true; ?>');

        $this->classLoader->setPackages($packages);

        $this->classLoader->loadClass('TestPackage\Subscriber\Foo');
        self::assertTrue(self::$testClassWasLoaded);

        self::$testClassWasLoaded = false;

        $this->classLoader->loadClass('TestPackage\Subscriber\Log\Bar');
        self::assertTrue(self::$testClassWasLoaded);
    }

    /**
     * @test
     */
    public function classesFromOverlayedPsr4PackagesAreOverwritten()
    {
        $this->classLoader = new ClassLoader();

        $mockPackage1 = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackage1->expects(self::any())->method('getNamespaces')->will(self::returnValue(['TestPackage\\Foo']));
        $mockPackage1->expects(self::any())->method('getFlattenedAutoloadConfiguration')->will(self::returnValue([
            [
                'namespace' => 'TestPackage\Foo',
                'classPath' => 'vfs://Test/Packages/Libraries/test/subPackage/src/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ]
        ]));

        $mockPackage2 = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackage2->expects(self::any())->method('getNamespaces')->will(self::returnValue(['TestPackage']));
        $mockPackage2->expects(self::any())->method('getFlattenedAutoloadConfiguration')->will(self::returnValue([
            [
                'namespace' => 'TestPackage',
                'classPath' => 'vfs://Test/Packages/Libraries/test/mainPackage/src/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ]
        ]));

        $packages = [$mockPackage2, $mockPackage1];
        mkdir('vfs://Test/Packages/Libraries/test/subPackage/src/', 0770, true);
        mkdir('vfs://Test/Packages/Libraries/test/mainPackage/src/Foo', 0770, true);
        file_put_contents('vfs://Test/Packages/Libraries/test/subPackage/src/Bar3.php', '<?php ' . __CLASS__ . '::$testClassWasOverwritten = true; ?>');
        file_put_contents('vfs://Test/Packages/Libraries/test/mainPackage/src/Foo/Bar3.php', '<?php ' . __CLASS__ . '::$testClassWasOverwritten = false; ?>');

        $this->classLoader->setPackages($packages);


        $this->classLoader->loadClass('TestPackage\Foo\Bar3');
        self::assertTrue(self::$testClassWasOverwritten);
    }
}
