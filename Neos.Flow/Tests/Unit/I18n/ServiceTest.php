<?php

declare(strict_types=1);

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\LocaleCollection;
use Neos\Flow\I18n\Service;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Locale Service class.
 */
final class ServiceTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        vfsStream::setup('Foo');
    }

    #[Test]
    public function getLocalizedFilenameReturnsCorrectlyLocalizedFilename()
    {
        $desiredLocale = new Locale('en_GB');
        $parentLocale = new Locale('en');
        $localeChain = ['en_GB' => $desiredLocale, 'en' => $parentLocale];
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en.png';

        mkdir(dirname($filename), 0777, true);
        file_put_contents($expectedFilename, 'FooBar');

        $service = $this->getMockBuilder(Service::class)->onlyMethods(['getLocaleChain'])->getMock();
        $service->expects($this->atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->willReturn(($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        self::assertEquals($expectedFilename, $result);
    }

    #[Test]
    public function getLocalizedFilenameIgnoresDotsInFilePath()
    {
        vfsStream::setup('Foo.Bar');

        $desiredLocale = new Locale('en_GB');
        $parentLocale = new Locale('en');
        $localeChain = ['en_GB' => $desiredLocale, 'en' => $parentLocale];
        $filename = 'vfs://Foo.Bar/Public/images';
        $expectedFilename = 'vfs://Foo.Bar/Public/images';

        mkdir($filename, 0777, true);

        $service = $this->getMockBuilder(Service::class)->onlyMethods(['getLocaleChain'])->getMock();
        $service->expects($this->atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->willReturn(($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        self::assertEquals($expectedFilename, $result);
    }

    #[Test]
    public function getLocalizedFilenameReturnsCorrectFilenameIfExtensionIsMissing()
    {
        mkdir('vfs://Foo/Bar/Public/images/', 0777, true);
        file_put_contents('vfs://Foo/Bar/Public/images/foobar.en_GB', 'FooBar');

        $filename = 'vfs://Foo/Bar/Public/images/foobar';
        $expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en_GB';

        $service = new Service();

        list($result, ) = $service->getLocalizedFilename($filename, new Locale('en_GB'), true);
        self::assertEquals($expectedFilename, $result);
    }

    #[Test]
    public function getLocalizedFilenameReturnsCorrectFilenameInStrictMode()
    {
        mkdir('vfs://Foo/Bar/Public/images/', 0777, true);
        file_put_contents('vfs://Foo/Bar/Public/images/foobar.en_GB.png', 'FooBar');

        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en_GB.png';

        $service = new Service();

        list($result, ) = $service->getLocalizedFilename($filename, new Locale('en_GB'), true);
        self::assertEquals($expectedFilename, $result);
    }

    #[Test]
    public function getLocalizedFilenameReturnsOriginalFilenameInStrictModeIfNoLocalizedFileExists()
    {
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';

        $service = new Service();

        list($result, ) = $service->getLocalizedFilename($filename, new Locale('pl'), true);
        self::assertEquals($filename, $result);
    }

    #[Test]
    public function getLocalizedFilenameReturnsOriginalFilenameIfNoLocalizedFileExists()
    {
        $filename = 'vfs://Foo/Bar/Public/images/foobar.png';
        $desiredLocale = new Locale('de_CH');
        $localeChain = ['de_CH' => $desiredLocale, 'en' => new Locale('en')];

        $service = $this->getMockBuilder(Service::class)->onlyMethods(['getLocaleChain'])->getMock();
        $service->expects($this->atLeastOnce())->method('getLocaleChain')->with($desiredLocale)->willReturn(($localeChain));

        list($result, ) = $service->getLocalizedFilename($filename, $desiredLocale);
        self::assertEquals($filename, $result);
    }

    #[Test]
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
        $mockPackage->method('getResourcesPath')->willReturn(('vfs://Foo/Bar/'));

        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getFlowPackages')->willReturn(([$mockPackage]));

        $mockLocaleCollection = $this->createMock(LocaleCollection::class);
        $mockLocaleCollection->expects($this->exactly(4))->method('addLocale');

        $mockSettings = ['i18n' => [
                                'defaultLocale' => 'sv_SE',
                                'fallbackRule' => ['strict' => false, 'order' => []],
                                'scan' => [
                                    'includePaths' => ['/Private/Translations/' => true],
                                    'excludePatterns' => [],
                                ]
        ]];

        $mockCache = $this->createMock(VariableFrontend::class);
        $mockCache->expects(self::once())->method('get')->with('availableLocales')->willReturn(false);

        $service = $this->getAccessibleMock(Service::class, []);
        $service->_set('localeBasePath', 'vfs://Foo/');
        $this->inject($service, 'packageManager', $mockPackageManager);
        $this->inject($service, 'localeCollection', $mockLocaleCollection);
        $service->injectSettings($mockSettings);
        $this->inject($service, 'cache', $mockCache);
        $service->initializeObject();
    }

    #[Test]
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
        $mockPackage->method('getResourcesPath')->willReturn(('vfs://Foo/Bar/'));

        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getFlowPackages')->willReturn(([$mockPackage]));

        $mockLocaleCollection = $this->createMock(LocaleCollection::class);
        $mockLocaleCollection->expects($this->exactly(2))->method('addLocale');

        $mockSettings = ['i18n' => [
                                'defaultLocale' => 'sv_SE',
                                'fallbackRule' => ['strict' => false, 'order' => []],
                                'scan' => [
                                    'includePaths' => ['/Private/Translations/' => true, '/Public/' => true],
                                    'excludePatterns' => ['/node_modules/' => true, '/\..*/' => true]
                                ]
        ]];

        $mockCache = $this->createMock(VariableFrontend::class);
        $mockCache->expects(self::once())->method('get')->with('availableLocales')->willReturn(false);

        $service = $this->getAccessibleMock(Service::class, []);
        $service->_set('localeBasePath', 'vfs://Foo/');
        $this->inject($service, 'packageManager', $mockPackageManager);
        $this->inject($service, 'localeCollection', $mockLocaleCollection);
        $service->injectSettings($mockSettings);
        $this->inject($service, 'cache', $mockCache);
        $service->initializeObject();
    }
}
