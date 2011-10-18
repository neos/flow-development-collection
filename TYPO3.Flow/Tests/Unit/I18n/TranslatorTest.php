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
		$mockLocalizationService->expects($this->once())->method('getDefaultLocale')->will($this->returnValue($this->sampleLocale));

		$mockPluralsReader = $this->getMock('TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm', 1, $this->sampleLocale)->will($this->returnValue(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

		$this->translator = new \TYPO3\FLOW3\I18n\Translator();
		$this->translator->injectLocalizationService($mockLocalizationService);
		$this->translator->injectPluralsReader($mockPluralsReader);
	}

	/**
	 * @test
	 */
	public function translatingIsDoneCorrectly() {
		$mockTranslationProvicer = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvicer->expects($this->once())->method('getTranslationByOriginalLabel', 'source', 'Untranslated label', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE)->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('TYPO3\FLOW3\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders', 'Translated label', array('value1', 'value2'), $this->sampleLocale)->will($this->returnValue('Formatted and translated label'));

		$this->translator->injectTranslationProvider($mockTranslationProvicer);
		$this->translator->injectFormatResolver($mockFormatResolver);

		$result = $this->translator->translateByOriginalLabel('Untranslated label', 'source', array('value1', 'value2'), 1);
		$this->assertEquals('Formatted and translated label', $result);
	}

	/**
	 * @test
	 */
	public function returnsOriginalLabelOrIdWhenTranslationNotAvailable() {
		$mockTranslationProvicer = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvicer->expects($this->once())->method('getTranslationByOriginalLabel', 'source', 'id', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE)->will($this->returnValue(FALSE));
		$mockTranslationProvicer->expects($this->once())->method('getTranslationById', 'source', 'id', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE)->will($this->returnValue(FALSE));

		$this->translator->injectTranslationProvider($mockTranslationProvicer);

		$result = $this->translator->translateByOriginalLabel('original label', 'source', array(), 1);
		$this->assertEquals('original label', $result);

		$result = $this->translator->translateById('id', 'source', array(), NULL, $this->sampleLocale);
		$this->assertEquals('id', $result);
	}
}

?>