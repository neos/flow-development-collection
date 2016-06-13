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

/**
 * Testcase for the Translator
 *
 */
class TranslatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\I18n\Locale
     */
    protected $defaultLocale;

    /**
     * @var \TYPO3\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->defaultLocale = new \TYPO3\Flow\I18n\Locale('en_GB');

        $mockLocalizationService = $this->createMock(\TYPO3\Flow\I18n\Service::class);
        $mockLocalizationService->expects($this->any())->method('getConfiguration')->will($this->returnValue(new \TYPO3\Flow\I18n\Configuration('en_GB')));

        $this->translator = new \TYPO3\Flow\I18n\Translator();
        $this->translator->injectLocalizationService($mockLocalizationService);
    }

    /**
     * @test
     */
    public function translatingIsDoneCorrectly()
    {
        $mockTranslationProvider = $this->createMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->defaultLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

        $mockFormatResolver = $this->createMock(\TYPO3\Flow\I18n\FormatResolver::class);
        $mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array('value1', 'value2'), $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

        $mockPluralsReader = $this->createMock(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::class);
        $mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1, $this->defaultLocale)->will($this->returnValue(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

        $this->translator->injectPluralsReader($mockPluralsReader);
        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $this->translator->injectFormatResolver($mockFormatResolver);

        $result = $this->translator->translateByOriginalLabel('Untranslated label', array('value1', 'value2'), 1, null, 'source', 'packageKey');
        $this->assertEquals('Formatted and translated label', $result);
    }

    /**
     * @test
     */
    public function returnsOriginalLabelWhenTranslationNotAvailable()
    {
        $mockTranslationProvider = $this->createMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('original label', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue(false));

        $this->translator->injectTranslationProvider($mockTranslationProvider);

        $result = $this->translator->translateByOriginalLabel('original label', array(), null, null, 'source', 'packageKey');
        $this->assertEquals('original label', $result);
    }

    /**
     * @test
     */
    public function returnsIdWhenTranslationNotAvailable()
    {
        $mockTranslationProvider = $this->createMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue('translated'));

        $this->translator->injectTranslationProvider($mockTranslationProvider);

        $result = $this->translator->translateById('id', array(), null, $this->defaultLocale, 'source', 'packageKey');
        $this->assertEquals('translated', $result);
    }

    /**
     * @test
     */
    public function translateByIdReturnsTranslationWhenNoArgumentsAreGiven()
    {
        $mockTranslationProvider = $this->createMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue(false));

        $this->translator->injectTranslationProvider($mockTranslationProvider);

        $result = $this->translator->translateById('id', array(), null, $this->defaultLocale, 'source', 'packageKey');
        $this->assertEquals('id', $result);
    }

    /**
     * @test
     */
    public function translateByOriginalLabelReturnsTranslationIfOneNumericArgumentIsGiven()
    {
        $mockTranslationProvider = $this->getAccessibleMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue('Translated label'));

        $mockFormatResolver = $this->createMock(\TYPO3\Flow\I18n\FormatResolver::class);
        $mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array(1.0), $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

        $mockPluralsReader = $this->createMock(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::class);
        $mockPluralsReader->expects($this->never())->method('getPluralForm');

        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $this->translator->injectFormatResolver($mockFormatResolver);
        $this->translator->injectPluralsReader($mockPluralsReader);

        $result = $this->translator->translateByOriginalLabel('Untranslated label', array(1.0), null, null, 'source', 'packageKey');
        $this->assertEquals('Formatted and translated label', $result);
    }

    /**
     * @test
     */
    public function translateByIdReturnsTranslationIfOneNumericArgumentIsGiven()
    {
        $mockTranslationProvider = $this->getAccessibleMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class);
        $mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, null, 'source', 'packageKey')->will($this->returnValue('Translated label'));

        $mockFormatResolver = $this->createMock(\TYPO3\Flow\I18n\FormatResolver::class);
        $mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array(1.0), $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

        $mockPluralsReader = $this->createMock(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::class);
        $mockPluralsReader->expects($this->never())->method('getPluralForm');

        $this->translator->injectTranslationProvider($mockTranslationProvider);
        $this->translator->injectFormatResolver($mockFormatResolver);
        $this->translator->injectPluralsReader($mockPluralsReader);

        $result = $this->translator->translateById('id', array(1.0), null, null, 'source', 'packageKey');
        $this->assertEquals('Formatted and translated label', $result);
    }
}
