<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the Locale Service class.
 *
 */
class ServiceTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        vfsStream::setup('Foo');
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsCorrectlyLocalizedFilename()
    {
        $desiredLocale = new \TYPO3\Flow\I18n\Locale('en_GB');
        $parentLocale = new \TYPO3\Flow\I18n\Locale('en');
        $localeChain = array('en_GB' => $desiredLocale, 'en' => $parentLocale);
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en.png';

        mkdir(dirname($filename), 0777, true);
        file_put_contents($expectedFilename, 'FooBar');

        $service = $this->getMock(\TYPO3\Flow\I18n\Service::class, array('getLocaleChain'));
        $service->expects($this->atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->will($this->returnValue($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        $this->assertEquals($expectedFilename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameIgnoresDotsInFilePath()
    {
        vfsStream::setup('Foo.Bar');

        $desiredLocale = new \TYPO3\Flow\I18n\Locale('en_GB');
        $parentLocale = new \TYPO3\Flow\I18n\Locale('en');
        $localeChain = array('en_GB' => $desiredLocale, 'en' => $parentLocale);
        $filename = 'vfs://Foo.Bar/Public/images';
        $expectedFilename = 'vfs://Foo.Bar/Public/images';

        mkdir($filename, 0777, true);

        $service = $this->getMock(\TYPO3\Flow\I18n\Service::class, array('getLocaleChain'));
        $service->expects($this->atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->will($this->returnValue($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        $this->assertEquals($expectedFilename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsCorrectFilenameIfExtensionIsMissing()
    {
        mkdir('vfs://Foo/Bar/Public/images/', 0777, true);
        file_put_contents('vfs://Foo/Bar/Public/images/foobar.en_GB', 'FooBar');

        $filename = 'vfs://Foo/Bar/Public/images/foobar';
        $expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en_GB';

        $service = new \TYPO3\Flow\I18n\Service();

        list($result, ) = $service->getLocalizedFilename($filename, new \TYPO3\Flow\I18n\Locale('en_GB'), true);
        $this->assertEquals($expectedFilename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsCorrectFilenameInStrictMode()
    {
        mkdir('vfs://Foo/Bar/Public/images/', 0777, true);
        file_put_contents('vfs://Foo/Bar/Public/images/foobar.en_GB.png', 'FooBar');

        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en_GB.png';

        $service = new \TYPO3\Flow\I18n\Service();

        list($result, ) = $service->getLocalizedFilename($filename, new \TYPO3\Flow\I18n\Locale('en_GB'), true);
        $this->assertEquals($expectedFilename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsOriginalFilenameInStrictModeIfNoLocalizedFileExists()
    {
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';

        $service = new \TYPO3\Flow\I18n\Service();

        list($result, ) = $service->getLocalizedFilename($filename, new \TYPO3\Flow\I18n\Locale('pl'), true);
        $this->assertEquals($filename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsOriginalFilenameIfNoLocalizedFileExists()
    {
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $desiredLocale = new \TYPO3\Flow\I18n\Locale('de_CH');
        $localeChain = array('de_CH' => $desiredLocale, 'en' => new \TYPO3\Flow\I18n\Locale('en'));

        $service = $this->getMock(\TYPO3\Flow\I18n\Service::class, array('getLocaleChain'));
        $service->expects($this->atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->will($this->returnValue($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        $this->assertEquals($filename, $result);
    }

    /**
     * @test
     */
    public function initializeCorrectlyGeneratesAvailableLocales()
    {
        mkdir('vfs://Foo/Bar/Private/Translations', 0777, true);
        foreach (array('en', 'sr_Cyrl_RS') as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Private/foobar.' . $localeIdentifier . '.baz', 'FooBar');
        }
        foreach (array('en_GB', 'sr') as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Private/Translations/' . $localeIdentifier . '.xlf', 'FooBar');
        }

        $mockPackage = $this->getMock(\TYPO3\Flow\Package\PackageInterface::class);
        $mockPackage->expects($this->any())->method('getResourcesPath')->will($this->returnValue('vfs://Foo/Bar/'));

        $mockPackageManager = $this->getMock(\TYPO3\Flow\Package\PackageManagerInterface::class);
        $mockPackageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue(array($mockPackage)));

        $mockLocaleCollection = $this->getMock(\TYPO3\Flow\I18n\LocaleCollection::class);
        $mockLocaleCollection->expects($this->exactly(4))->method('addLocale');

        $mockSettings = array('i18n' => array('defaultLocale' => 'sv_SE', 'fallbackRule' => array('strict' => false, 'order' => array()), 'scan' => array('paths' => array('^/Private/' => true))));

        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\VariableFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->once())->method('has')->with('availableLocales')->will($this->returnValue(false));

        $service = $this->getAccessibleMock(\TYPO3\Flow\I18n\Service::class, array('dummy'));
        $service->_set('localeBasePath', 'vfs://Foo/');
        $this->inject($service, 'packageManager', $mockPackageManager);
        $this->inject($service, 'localeCollection', $mockLocaleCollection);
        $service->injectSettings($mockSettings);
        $this->inject($service, 'cache', $mockCache);
        $service->initializeObject();
    }

    /**
     * @test
     */
    public function initializeCorrectlySkipsExcludedPathsFromScanningLocales()
    {
        mkdir('vfs://Foo/Bar/Public/node_modules/foo/bar', 0777, true);
        mkdir('vfs://Foo/Bar/Private/Translations', 0777, true);
        foreach (array('en', 'sr_Cyrl_RS') as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Public/node_modules/foo/bar/foobar.' . $localeIdentifier . '.baz', 'FooBar');
        }
        foreach (array('en_GB', 'sr') as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Private/Translations/' . $localeIdentifier . '.xlf', 'FooBar');
        }

        $mockPackage = $this->getMock(\TYPO3\Flow\Package\PackageInterface::class);
        $mockPackage->expects($this->any())->method('getResourcesPath')->will($this->returnValue('vfs://Foo/Bar/'));

        $mockPackageManager = $this->getMock(\TYPO3\Flow\Package\PackageManagerInterface::class);
        $mockPackageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue(array($mockPackage)));

        $mockLocaleCollection = $this->getMock(\TYPO3\Flow\I18n\LocaleCollection::class);
        $mockLocaleCollection->expects($this->exactly(2))->method('addLocale');

        $mockSettings = array('i18n' => array('defaultLocale' => 'sv_SE', 'fallbackRule' => array('strict' => false, 'order' => array()), 'scan' => array('paths' => array('^/Private/' => true, '^/Public/' => true, '/node_modules/' => false))));

        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\VariableFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->once())->method('has')->with('availableLocales')->will($this->returnValue(false));

        $service = $this->getAccessibleMock(\TYPO3\Flow\I18n\Service::class, array('dummy'));
        $service->_set('localeBasePath', 'vfs://Foo/');
        $this->inject($service, 'packageManager', $mockPackageManager);
        $this->inject($service, 'localeCollection', $mockLocaleCollection);
        $service->injectSettings($mockSettings);
        $this->inject($service, 'cache', $mockCache);
        $service->initializeObject();
    }
}
