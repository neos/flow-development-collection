<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
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
class TranslatorTest extends \TYPO3\Flow\Tests\UnitTestCase {

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
	public function setUp() {
		$this->defaultLocale = new \TYPO3\Flow\I18n\Locale('en_GB');

		$mockLocalizationService = $this->getMock('TYPO3\Flow\I18n\Service');
		$mockLocalizationService->expects($this->any())->method('getConfiguration')->will($this->returnValue(new \TYPO3\Flow\I18n\Configuration('en_GB')));

		$this->translator = new \TYPO3\Flow\I18n\Translator();
		$this->translator->injectLocalizationService($mockLocalizationService);
	}

	/**
	 * @test
	 */
	public function translatingIsDoneCorrectly() {
		$mockTranslationProvider = $this->getMock('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->defaultLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('TYPO3\Flow\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array('value1', 'value2'), $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

		$mockPluralsReader = $this->getMock('TYPO3\Flow\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1, $this->defaultLocale)->will($this->returnValue(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

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
		$mockTranslationProvider = $this->getMock('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('original label', $this->defaultLocale, NULL, 'source', 'packageKey')->will($this->returnValue(FALSE));

		$this->translator->injectTranslationProvider($mockTranslationProvider);

		$result = $this->translator->translateByOriginalLabel('original label', array(), NULL, NULL, 'source', 'packageKey');
		$this->assertEquals('original label', $result);
	}

	/**
	 * @test
	 */
	public function returnsIdWhenTranslationNotAvailable() {
		$mockTranslationProvider = $this->getMock('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, NULL, 'source', 'packageKey')->will($this->returnValue('translated'));

		$this->translator->injectTranslationProvider($mockTranslationProvider);

		$result = $this->translator->translateById('id', array(), NULL, $this->defaultLocale, 'source', 'packageKey');
		$this->assertEquals('translated', $result);
	}

	/**
	 * @test
	 */
	public function translateByIdReturnsTranslationWhenNoArgumentsAreGiven() {
		$mockTranslationProvider = $this->getMock('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, NULL, 'source', 'packageKey')->will($this->returnValue(FALSE));

		$this->translator->injectTranslationProvider($mockTranslationProvider);

		$result = $this->translator->translateById('id', array(), NULL, $this->defaultLocale, 'source', 'packageKey');
		$this->assertEquals('id', $result);
	}

	/**
	 * @test
	 */
	public function quantityIsDeterminedAutomaticallyIfOneNumricArgumentIsGivenToTranslateByOriginalLabel() {
		$mockTranslationProvider = $this->getAccessibleMock('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationByOriginalLabel')->with('Untranslated label', $this->defaultLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('TYPO3\Flow\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array(1.0), $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

		$mockPluralsReader = $this->getMock('TYPO3\Flow\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1.0, $this->defaultLocale)->will($this->returnValue(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

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
		$mockTranslationProvider = $this->getAccessibleMock('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvider->expects($this->once())->method('getTranslationById')->with('id', $this->defaultLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, 'source', 'packageKey')->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('TYPO3\Flow\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders')->with('Translated label', array(1.0), $this->defaultLocale)->will($this->returnValue('Formatted and translated label'));

		$mockPluralsReader = $this->getMock('TYPO3\Flow\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm')->with(1.0, $this->defaultLocale)->will($this->returnValue(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

		$this->translator->injectTranslationProvider($mockTranslationProvider);
		$this->translator->injectFormatResolver($mockFormatResolver);
		$this->translator->injectPluralsReader($mockPluralsReader);

		$result = $this->translator->translateById('id', array(1.0), NULL, NULL, 'source', 'packageKey');
		$this->assertEquals('Formatted and translated label', $result);
	}

}
