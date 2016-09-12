<?php
namespace TYPO3\Flow\Tests\Functional\I18n\Xliff\Service;

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
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Composer\ComposerUtility;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Xliff\Service\XliffFileProvider;
use TYPO3\Flow\Package\Package;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcases for the XLIFF file provider
 */
class XliffFileProviderTest extends FunctionalTestCase
{
    /**
     * @var XliffFileProvider
     */
    protected $fileProvider;


    /**
     * Initialize dependencies
     */
    public function setUp()
    {
        parent::setUp();
        $this->fileProvider = $this->objectManager->get(XliffFileProvider::class);

        $packages = $this->setUpPackages();
        ComposerUtility::flushCaches();

        $mockPackageManager = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockPackageManager->expects($this->any())
            ->method('getActivePackages')
            ->will($this->returnValue($packages));
        $this->inject($this->fileProvider, 'packageManager', $mockPackageManager);

        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Flow_I18n_XmlModelCache')->flush();
    }

    /**
     * @return array|Package[]
     */
    protected function setUpPackages()
    {
        vfsStream::setup('Packages');

        $basePackage = $this->setUpPackage('BasePackage', [
            'de/BasePackage.Unmerged.xlf' => 'Resources/Private/Translations/de/Unmerged.xlf',
            'de/BasePackage.Main.xlf' => 'Resources/Private/Translations/de/Main.xlf',
            'de/BasePackage.DependentMain.xlf' => 'Resources/Private/Translations/de/DependentMain.xlf'
        ]);
        $packages[$basePackage->getPackageKey()] = $basePackage;

        $dependentPackage = $this->setUpPackage('DependentPackage', [
            'de/DependentPackage.WithoutBase.xlf' => 'Resources/Private/Translations/de/WithoutBase.xlf',
            'de/DependentPackage.BaseMain.xlf' => 'Resources/Private/Translations/de/BaseMain.xlf',
            'de/DependentPackage.Main.xlf' => 'Resources/Private/Translations/de/Main.xlf'
        ]);
        $packages[$dependentPackage->getPackageKey()] = $dependentPackage;

        return $packages;
    }

    /**
     * @param string $packageName
     * @param array $filePaths
     * @return Package
     */
    protected function setUpPackage($packageName, array $filePaths)
    {
        $vendorName = 'Vendor';
        $packagePath = 'vfs://Packages/Application/' . $vendorName . '/' . $packageName . '/';
        $composerName = strtolower($vendorName) . '/' . strtolower($packageName);
        $packageKey = $vendorName . '.' . $packageName;
        mkdir($packagePath, 0700, true);
        mkdir($packagePath . 'Resources/Private/Translations/en/', 0700, true);
        mkdir($packagePath . 'Resources/Private/Translations/de/', 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "' . $composerName . '", "type": "flow-test"}');

        $fixtureBasePath = __DIR__ . '/../Fixtures/';
        foreach ($filePaths as $fixturePath => $targetPath) {
            copy($fixtureBasePath . $fixturePath, $packagePath . $targetPath);
        }

        return new Package($packageKey, $composerName, $packagePath);
    }

    /**
     * @test
     */
    public function fileProviderReturnsUnchangedContentForFileWithoutOverride()
    {
        $fileData = $this->fileProvider->getMergedFileData('Vendor.BasePackage:Unmerged', new Locale('de'));
        $this->assertSame([
            'key1' => [
                [
                    'source' => 'Source string',
                    'target' => 'Übersetzte Zeichenkette'
                ]
            ]
        ], $fileData['translationUnits']);
    }

    /**
     * @test
     */
    public function fileProviderMergesOverrideFromLaterLoadedPackageDeclaredByOriginalAndProductName()
    {
        $fileData = $this->fileProvider->getMergedFileData('Vendor.BasePackage:Main', new Locale('de'));
        $this->assertSame([
            'key1' => [
                [
                    'source' => 'Source string',
                    'target' => 'Anders übersetzte Zeichenkette'
                ]
            ]
        ], $fileData['translationUnits']);
    }

    /**
     * @test
     */
    public function fileProviderReturnsLaterLoadedPackageDeclarationByOriginalAndProductNameIfNoOriginalPresent()
    {
        $fileData = $this->fileProvider->getMergedFileData('Vendor.BasePackage:WithoutBase', new Locale('de'));
        $this->assertSame([
            'key1' => [
                [
                    'source' => 'Additional source string',
                    'target' => 'Übersetzte zusätzliche Zeichenkette'
                ]
            ]
        ], $fileData['translationUnits']);
    }

    /**
     * @test
     */
    public function fileProviderDoesNotMergeOverrideFromEarlierLoadedPackageDeclaredByOriginalAndProductName()
    {
        $fileData = $this->fileProvider->getMergedFileData('Vendor.DependentPackage:Main', new Locale('de'));
        $this->assertSame([
            'key1' => [
                [
                    'source' => 'Source string',
                    'target' => 'Übersetzte Zeichenkette'
                ]
            ]
        ], $fileData['translationUnits']);
    }
}
