<?php
namespace Neos\Flow\Tests\Unit\I18n;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use org\bovigo\vfs\vfsStream;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the Locale Service class.
 */
class ServiceTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        vfsStream::setup('Foo');
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsCorrectlyLocalizedFilename()
    {
        $desiredLocale = new I18n\Locale('en_GB');
        $parentLocale = new I18n\Locale('en');
        $localeChain = ['en_GB' => $desiredLocale, 'en' => $parentLocale];
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en.png';

        mkdir(dirname($filename), 0777, true);
        file_put_contents($expectedFilename, 'FooBar');

        $service = $this->getMockBuilder(I18n\Service::class)->setMethods(['getLocaleChain'])->getMock();
        $service->expects(self::atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->will(self::returnValue($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        self::assertEquals($expectedFilename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameIgnoresDotsInFilePath()
    {
        vfsStream::setup('Foo.Bar');

        $desiredLocale = new I18n\Locale('en_GB');
        $parentLocale = new I18n\Locale('en');
        $localeChain = ['en_GB' => $desiredLocale, 'en' => $parentLocale];
        $filename = 'vfs://Foo.Bar/Public/images';
        $expectedFilename = 'vfs://Foo.Bar/Public/images';

        mkdir($filename, 0777, true);

        $service = $this->getMockBuilder(I18n\Service::class)->setMethods(['getLocaleChain'])->getMock();
        $service->expects(self::atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->will(self::returnValue($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        self::assertEquals($expectedFilename, $result);
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

        $service = new I18n\Service();

        list($result, ) = $service->getLocalizedFilename($filename, new I18n\Locale('en_GB'), true);
        self::assertEquals($expectedFilename, $result);
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

        $service = new I18n\Service();

        list($result, ) = $service->getLocalizedFilename($filename, new I18n\Locale('en_GB'), true);
        self::assertEquals($expectedFilename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsOriginalFilenameInStrictModeIfNoLocalizedFileExists()
    {
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';

        $service = new I18n\Service();

        list($result, ) = $service->getLocalizedFilename($filename, new I18n\Locale('pl'), true);
        self::assertEquals($filename, $result);
    }

    /**
     * @test
     */
    public function getLocalizedFilenameReturnsOriginalFilenameIfNoLocalizedFileExists()
    {
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $desiredLocale = new I18n\Locale('de_CH');
        $localeChain = ['de_CH' => $desiredLocale, 'en' => new I18n\Locale('en')];

        $service = $this->getMockBuilder(I18n\Service::class)->setMethods(['getLocaleChain'])->getMock();
        $service->expects(self::atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->will(self::returnValue($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        self::assertEquals($filename, $result);
    }

    /**
     * @test
     */
    public function initializeCorrectlyGeneratesAvailableLocales()
    {
        mkdir('vfs://Foo/Bar/Public', 0777, true);
        mkdir('vfs://Foo/Bar/Private/Translations', 0777, true);
        foreach (['en', 'sr_Cyrl_RS'] as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Public/foobar.' . $localeIdentifier . '.baz', 'FooBar');
        }
        foreach (['en_GB', 'sr'] as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Private/Translations/' . $localeIdentifier . '.xlf', 'FooBar');
        }
        foreach (['de_DE', 'de_CH'] as $localeIdentifier) {
            mkdir('vfs://Foo/Bar/Private/Translations/' . $localeIdentifier, 0777, true);
            file_put_contents('vfs://Foo/Bar/Private/Translations/' . $localeIdentifier . '/Main.xlf', 'FooBar');
        }

        $mockPackage = $this->createMock(FlowPackageInterface::class);
        $mockPackage->expects(self::any())->method('getResourcesPath')->will(self::returnValue('vfs://Foo/Bar/'));

        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getFlowPackages')->will(self::returnValue([$mockPackage]));

        $mockLocaleCollection = $this->createMock(I18n\LocaleCollection::class);
        $mockLocaleCollection->expects(self::exactly(4))->method('addLocale');

        $mockSettings = ['i18n' => [
                                'defaultLocale' => 'sv_SE',
                                'fallbackRule' => ['strict' => false, 'order' => []],
                                'scan' => [
                                    'includePaths' => ['/Private/Translations/' => true],
                                    'excludePatterns' => [],
                                ]
        ]];

        $mockCache = $this->createMock(VariableFrontend::class);
        $mockCache->expects(self::once())->method('has')->with('availableLocales')->will(self::returnValue(false));

        $service = $this->getAccessibleMock(I18n\Service::class, ['dummy']);
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
        mkdir('vfs://Foo/Bar/Public/app/.git/refs/heads', 0777, true);
        mkdir('vfs://Foo/Bar/Private/Translations', 0777, true);
        foreach (['en', 'sr_Cyrl_RS'] as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Public/node_modules/foo/bar/foobar.' . $localeIdentifier . '.baz', 'FooBar');
            file_put_contents('vfs://Foo/Bar/Public/app/.git/refs/heads/' . $localeIdentifier . '.dev', 'FooBar');
        }

        foreach (['en_GB', 'sr'] as $localeIdentifier) {
            file_put_contents('vfs://Foo/Bar/Private/Translations/' . $localeIdentifier . '.xlf', 'FooBar');
        }

        $mockPackage = $this->createMock(FlowPackageInterface::class);
        $mockPackage->expects(self::any())->method('getResourcesPath')->will(self::returnValue('vfs://Foo/Bar/'));

        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getFlowPackages')->will(self::returnValue([$mockPackage]));

        $mockLocaleCollection = $this->createMock(I18n\LocaleCollection::class);
        $mockLocaleCollection->expects(self::exactly(2))->method('addLocale');

        $mockSettings = ['i18n' => [
                                'defaultLocale' => 'sv_SE',
                                'fallbackRule' => ['strict' => false, 'order' => []],
                                'scan' => [
                                    'includePaths' => ['/Private/Translations/' => true, '/Public/' => true],
                                    'excludePatterns' => ['/node_modules/' => true, '/\..*/' => true]
                                ]
        ]];

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects(self::once())->method('has')->with('availableLocales')->will(self::returnValue(false));

        $service = $this->getAccessibleMock(I18n\Service::class, ['dummy']);
        $service->_set('localeBasePath', 'vfs://Foo/');
        $this->inject($service, 'packageManager', $mockPackageManager);
        $this->inject($service, 'localeCollection', $mockLocaleCollection);
        $service->injectSettings($mockSettings);
        $this->inject($service, 'cache', $mockCache);
        $service->initializeObject();
    }
}
