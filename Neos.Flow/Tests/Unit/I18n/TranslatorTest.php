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

use Neos\Flow\I18n\Cldr\Reader\PluralsReader;
use Neos\Flow\I18n\TranslationProvider\XliffTranslationProvider;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the Translator
 */
class TranslatorTest extends UnitTestCase
{
    /**
     * @var I18n\Locale
     */
    protected $defaultLocale;

    /**
     * @var I18n\Translator
     */
    protected $translator;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->defaultLocale = new I18n\Locale('en_GB');

        $mockLocalizationService = $this->createMock(I18n\Service::class);
        $mockLocalizationService->expects($this->any())->method('getConfiguration')->will($this->returnValue(new I18n\Configuration('en_GB')));

        $this->translator = new I18n\Translator();
        $this->translator->injectLocalizationService($mockLocalizationService);
    }

    /**
     * @test
     */
    public function translatingIsDoneCorrectly()
    {
        $mockTranslationProvider = $this->createMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->defaultLocale, PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

        $mockFormatResolver = $this->createMock(I18n\FormatResolver::class);
        $mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', ['value1', 'value2'], $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

        $mockPluralsReader = $this->createMock(PluralsReader::class);
        $mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1, $this->defaultLocale)->will($this->returnValue(PluralsReader::RULE_ONE));

        $this->translator->injectPluralsReader($mockPluralsReader);
        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $this->translator->injectFormatResolver($mockFormatResolver);

        $result = $this->translator->translateByOriginalLabel('Untranslated label', ['value1', 'value2'], 1, null, 'source', 'packageKey');
        $this->assertEquals('Formatted and translated label', $result);
    }

    /**
     * @test
     */
    public function translateByOriginalLabelReturnsOriginalLabelWhenTranslationNotAvailable()
    {
        $mockTranslationProvider = $this->createMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('original label', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue(false));

        $this->translator->injectTranslationProvider($mockTranslationProvider);

        $result = $this->translator->translateByOriginalLabel('original label', [], null, null, 'source', 'packageKey');
        $this->assertEquals('original label', $result);
    }

    /**
     * @test
     */
    public function translateByIdReturnsNullWhenTranslationNotAvailable()
    {
        $mockTranslationProvider = $this->createMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue(false));

        $this->translator->injectTranslationProvider($mockTranslationProvider);

        $result = $this->translator->translateById('id', [], null, $this->defaultLocale, 'source', 'packageKey');
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function translateByIdReturnsTranslationWhenNoArgumentsAreGiven()
    {
        $mockTranslationProvider = $this->createMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue('translatedId'));

        $this->translator->injectTranslationProvider($mockTranslationProvider);

        $result = $this->translator->translateById('id', [], null, $this->defaultLocale, 'source', 'packageKey');
        $this->assertEquals('translatedId', $result);
    }

    /**
     * @test
     */
    public function translateByOriginalLabelReturnsTranslationIfOneNumericArgumentIsGiven()
    {
        $mockTranslationProvider = $this->getAccessibleMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue('Translated label'));

        $mockFormatResolver = $this->createMock(I18n\FormatResolver::class);
        $mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', [1.0], $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

        $mockPluralsReader = $this->createMock(PluralsReader::class);
        $mockPluralsReader->expects($this->never())->method('getPluralForm');

        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $this->translator->injectFormatResolver($mockFormatResolver);
        $this->translator->injectPluralsReader($mockPluralsReader);

        $result = $this->translator->translateByOriginalLabel('Untranslated label', [1.0], null, null, 'source', 'packageKey');
        $this->assertEquals('Formatted and translated label', $result);
    }

    /**
     * @test
     */
    public function translateByIdReturnsTranslationIfOneNumericArgumentIsGiven()
    {
        $mockTranslationProvider = $this->getAccessibleMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue('Translated label'));

        $mockFormatResolver = $this->createMock(I18n\FormatResolver::class);
        $mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', [1.0], $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

        $mockPluralsReader = $this->createMock(PluralsReader::class);
        $mockPluralsReader->expects($this->never())->method('getPluralForm');

        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $this->translator->injectFormatResolver($mockFormatResolver);
        $this->translator->injectPluralsReader($mockPluralsReader);

        $result = $this->translator->translateById('id', [1.0], null, null, 'source', 'packageKey');
        $this->assertEquals('Formatted and translated label', $result);
    }

    /**
     * @return array
     */
    public function translateByOriginalLabelDataProvider()
    {
        return [
            ['originalLabel' => 'Some label', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['originalLabel' => 'Some label', 'translatedLabel' => false, 'expectedResult' => 'Some label'],
        ];
    }

    /**
     * @test
     * @dataProvider translateByOriginalLabelDataProvider
     * @param string $originalLabel
     * @param string $translatedLabel
     * @param string $expectedResult
     */
    public function translateByOriginalLabelTests($originalLabel, $translatedLabel, $expectedResult)
    {
        $mockTranslationProvider = $this->createMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with($originalLabel)->will($this->returnValue($translatedLabel));

        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $actualResult = $this->translator->translateByOriginalLabel($originalLabel);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @return array
     */
    public function translateByIdDataProvider()
    {
        return [
            ['id' => 'some.id', 'translatedId' => 'Translated id', 'expectedResult' => 'Translated id'],
            ['id' => 'some.id', 'translatedId' => false, 'expectedResult' => null],
        ];
    }

    /**
     * @test
     * @dataProvider translateByIdDataProvider
     * @param string $id
     * @param string $translatedId
     * @param string $expectedResult
     */
    public function translateByIdTests($id, $translatedId, $expectedResult)
    {
        $mockTranslationProvider = $this->createMock(XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationById')->with($id)->will($this->returnValue($translatedId));

        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $actualResult = $this->translator->translateById($id);
        $this->assertSame($expectedResult, $actualResult);
    }
}
