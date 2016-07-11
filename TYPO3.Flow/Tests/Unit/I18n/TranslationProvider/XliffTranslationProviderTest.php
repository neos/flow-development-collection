<?php
namespace TYPO3\Flow\Tests\Unit\I18n\TranslationProvider;

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
 * Testcase for the XliffTranslationProvider
 *
 */
class XliffTranslationProviderTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var string
     */
    protected $sampleSourceName;

    /**
     * @var string
     */
    protected $samplePackageKey;

    /**
     * @var \TYPO3\Flow\I18n\Locale
     */
    protected $sampleLocale;

    /**
     * @var \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader
     */
    protected $mockPluralsReader;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->sampleSourceName = 'foo';
        $this->samplePackageKey = 'TYPO3.Flow';
        $this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en_GB');

        $this->mockPluralsReader = $this->createMock(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::class);
    }

    /**
     * @test
     */
    public function returnsTranslatedLabelWhenOriginalLabelProvided()
    {
        $mockModel = $this->createMock(\TYPO3\Flow\I18n\Xliff\XliffModel::class, array(), array('foo', $this->sampleLocale));
        $mockModel->expects($this->once())->method('getTargetBySource')->with('bar', 0)->will($this->returnValue('baz'));

        $this->mockPluralsReader->expects($this->once())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

        $translationProvider = $this->getAccessibleMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class, array('getModel'));
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->expects($this->once())->method('getModel')->with($this->samplePackageKey, $this->sampleSourceName, $this->sampleLocale)->will($this->returnValue($mockModel));

        $result = $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
        $this->assertEquals('baz', $result);
    }

    /**
     * @test
     */
    public function returnsTranslatedLabelWhenLabelIdProvided()
    {
        $mockModel = $this->createMock(\TYPO3\Flow\I18n\Xliff\XliffModel::class, array(), array('foo', $this->sampleLocale));
        $mockModel->expects($this->once())->method('getTargetByTransUnitId')->with('bar', 1)->will($this->returnValue('baz'));

        $this->mockPluralsReader->expects($this->any())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

        $translationProvider = $this->getAccessibleMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class, array('getModel'));
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->expects($this->once())->method('getModel')->with($this->samplePackageKey, $this->sampleSourceName, $this->sampleLocale)->will($this->returnValue($mockModel));

        $result = $translationProvider->getTranslationById('bar', $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_OTHER, $this->sampleSourceName, $this->samplePackageKey);
        $this->assertEquals('baz', $result);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByOriginalLabelThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->mockPluralsReader->expects($this->any())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

        $translationProvider = $this->getMockBuilder(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class)->setMethods(array('getModel'))->getMock();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByIdThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->mockPluralsReader->expects($this->any())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));

        $translationProvider = $this->getMockBuilder(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class)->setMethods(array('getModel'))->getMock();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationById('bar', $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }

    /**
     * @test
     */
    public function getModelSetsCorrectLocaleInModel()
    {
        $expectedSourcePath = 'expectedSourcePath';
        $expectedLocale = new \TYPO3\Flow\I18n\Locale('za');

        $mockLocalizationService = $this->createMock(\TYPO3\Flow\I18n\Service::class);
        $mockLocalizationService->expects($this->once())->method('getXliffFilenameAndPath')->will($this->returnValue(array($expectedSourcePath, $expectedLocale)));

        $translationProvider = $this->getAccessibleMock(\TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider::class, array('dummy'));
        $translationProvider->injectLocalizationService($mockLocalizationService);

        $model = $translationProvider->_call('getModel', $this->samplePackageKey, $this->sampleSourceName, $this->sampleLocale);
        $this->assertAttributeSame($expectedLocale, 'locale', $model);
        $this->assertAttributeSame($expectedSourcePath, 'sourcePath', $model);
    }
}
