<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n;

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
 * Testcase for the Translator
 *
 */
class TranslatorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @var \TYPO3\FLOW3\I18n\Translator
	 */
	protected $translator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->sampleLocale = new \TYPO3\FLOW3\I18n\Locale('en_GB');

		$mockLocalizationService = $this->getMock('TYPO3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->any())->method('getCurrentLocale')->will($this->returnValue($this->sampleLocale));

		$this->translator = new \TYPO3\FLOW3\I18n\Translator();
		$this->translator->injectLocalizationService($mockLocalizationService);
	}

	/**
	 * @test
	 */
	public function translatingIsDoneCorrectly() {
		$mockTranslationProvider = $this->getMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('TYPO3\FLOW3\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array('value1', 'value2'), $this->sampleLocale)->will($this->returnValue('Formatted and translated label'));

		$mockPluralsReader = $this->getMock('TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1, $this->sampleLocale)->will($this->returnValue(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

		$this->translator->injectPluralsReader($mockPluralsReader);
		$this->translator->injectTranslationProvider($mockTranslationProvider);
		$this->translator->injectFormatResolver($mockFormatResolver);

			$result = $this->translator->translateByOriginalLabel('Untranslated label', array('value1', 'value2'), 1, NULL, 'source', 'packageKey');
		$this->assertEquals('Formatted and translated label', $result);
	}

	/**
	 * @test
	 */
	public function returnsOriginalLabelWhenTranslationNotAvailable() {
		$mockTranslationProvider = $this->getMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('original label', $this->sampleLocale, NULL, 'source', 'packageKey')->will($this->returnValue(FALSE));

		$this->translator->injectTranslationProvider($mockTranslationProvider);

		$result = $this->translator->translateByOriginalLabel('original label', array(), NULL, NULL, 'source', 'packageKey');
		$this->assertEquals('original label', $result);
	}

	/**
	 * @test
	 */
	public function returnsIdWhenTranslationNotAvailable() {
		$mockTranslationProvider = $this->getMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->sampleLocale, NULL, 'source', 'packageKey')->will($this->returnValue(FALSE));

		$this->translator->injectTranslationProvider($mockTranslationProvider);

		$result = $this->translator->translateById('id', array(), NULL, $this->sampleLocale, 'source', 'packageKey');
		$this->assertEquals('id', $result);
	}

	/**
	 * @test
	 */
	public function quantityIsDeterminedAutomaticallyIfOneNumricArgumentIsGivenToTranslateByOriginalLabel() {
		$mockTranslationProvider = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('TYPO3\FLOW3\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array(1.0), $this->sampleLocale)->will($this->returnValue('Formatted and translated label'));

		$mockPluralsReader = $this->getMock('TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1.0, $this->sampleLocale)->will($this->returnValue(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

		$this->translator->injectTranslationProvider($mockTranslationProvider);
		$this->translator->injectFormatResolver($mockFormatResolver);
		$this->translator->injectPluralsReader($mockPluralsReader);

		$result = $this->translator->translateByOriginalLabel('Untranslated label', array(1.0), NULL, NULL, 'source', 'packageKey');
		$this->assertEquals('Formatted and translated label', $result);
	}

	/**
	 * @test
	 */
	public function quantityIsDeterminedAutomaticallyIfOneNumricArgumentIsGivenToTranslateById() {
		$mockTranslationProvider = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('TYPO3\FLOW3\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array(1.0), $this->sampleLocale)->will($this->returnValue('Formatted and translated label'));

		$mockPluralsReader = $this->getMock('TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1.0, $this->sampleLocale)->will($this->returnValue(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

		$this->translator->injectTranslationProvider($mockTranslationProvider);
		$this->translator->injectFormatResolver($mockFormatResolver);
		$this->translator->injectPluralsReader($mockPluralsReader);

		$result = $this->translator->translateById('id', array(1.0), NULL, NULL, 'source', 'packageKey');
		$this->assertEquals('Formatted and translated label', $result);
	}

}

?>