<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n\TranslationProvider;

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
 * Testcase for the XliffTranslationProvider
 *
 */
class XliffTranslationProviderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	protected $sampleSourceName;

	/**
	 * @var string
	 */
	protected $samplePackageKey;

	/**
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @var \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader
	 */
	protected $mockPluralsReader;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->sampleSourceName = 'foo';
		$this->samplePackageKey = 'TYPO3.FLOW3';
		$this->sampleLocale = new \TYPO3\FLOW3\I18n\Locale('en_GB');

		$this->mockPluralsReader = $this->getMock('TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader');
	}

	/**
	 * @test
	 */
	public function returnsTranslatedLabelWhenOriginalLabelProvided() {
		$mockModel = $this->getMock('TYPO3\FLOW3\I18n\Xliff\XliffModel', array(), array('foo', $this->sampleLocale));
		$mockModel->expects($this->once())->method('getTargetBySource')->with('bar', 0)->will($this->returnValue('baz'));

		$this->mockPluralsReader->expects($this->once())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

		$translationProvider = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('getModel'));
		$translationProvider->injectPluralsReader($this->mockPluralsReader);
		$translationProvider->expects($this->once())->method('getModel')->with($this->samplePackageKey, $this->sampleSourceName, $this->sampleLocale)->will($this->returnValue($mockModel));

		$result = $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
		$this->assertEquals('baz', $result);
	}

	/**
	 * @test
	 */
	public function returnsTranslatedLabelWhenLabelIdProvided() {
		$mockModel = $this->getMock('TYPO3\FLOW3\I18n\Xliff\XliffModel', array(), array('foo', $this->sampleLocale));
		$mockModel->expects($this->once())->method('getTargetByTransUnitId')->with('bar', 1)->will($this->returnValue('baz'));

		$this->mockPluralsReader->expects($this->any())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

		$translationProvider = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('getModel'));
		$translationProvider->injectPluralsReader($this->mockPluralsReader);
		$translationProvider->expects($this->once())->method('getModel')->with($this->samplePackageKey, $this->sampleSourceName, $this->sampleLocale)->will($this->returnValue($mockModel));

		$result = $translationProvider->getTranslationById('bar', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER, $this->sampleSourceName, $this->samplePackageKey);
		$this->assertEquals('baz', $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\I18n\TranslationProvider\Exception\InvalidPluralFormException
	 */
	public function getTranslationByOriginalLabelThrowsExceptionWhenInvalidPluralFormProvided() {
		$this->mockPluralsReader->expects($this->any())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

		$translationProvider = $this->getMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('getModel'));
		$translationProvider->injectPluralsReader($this->mockPluralsReader);

		$translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\I18n\TranslationProvider\Exception\InvalidPluralFormException
	 */
	public function getTranslationByIdThrowsExceptionWhenInvalidPluralFormProvided() {
		$this->mockPluralsReader->expects($this->any())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

		$translationProvider = $this->getMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('getModel'));
		$translationProvider->injectPluralsReader($this->mockPluralsReader);

		$translationProvider->getTranslationById('bar', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
	}

	/**
	 * @test
	 */
	public function getModelSetsCorrectLocaleInModel() {
		$expectedSourcePath = 'expectedSourcePath';
		$expectedLocale = new \TYPO3\FLOW3\I18n\Locale('za');

		$mockLocalizationService = $this->getMock('TYPO3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->once())->method('getLocalizedFilename')->will($this->returnValue(array($expectedSourcePath, $expectedLocale)));

		$translationProvider = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('dummy'));
		$translationProvider->injectLocalizationService($mockLocalizationService);

		$model = $translationProvider->_call('getModel', $this->samplePackageKey, $this->sampleSourceName, $this->sampleLocale);
		$this->assertAttributeSame($expectedLocale, 'locale', $model);
		$this->assertAttributeSame($expectedSourcePath, 'sourcePath', $model);
	}
}

?>