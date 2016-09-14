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

use \TYPO3\Flow\I18n;

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
     * @var string
     */
    protected $sampleFileIdentifier;

    /**
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * @var I18n\Cldr\Reader\PluralsReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPluralsReader;

    /**
     * @var I18n\Xliff\Service\XliffFileProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockFileProvider;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->sampleSourceName = 'foo';
        $this->samplePackageKey = 'TYPO3.Flow';
        $this->sampleFileIdentifier = 'TYPO3.Flow:foo';
        $this->sampleLocale = new I18n\Locale('en_GB');

        $this->mockPluralsReader = $this->createMock(I18n\Cldr\Reader\PluralsReader::class);
        $this->mockFileProvider = $this->createMock(I18n\Xliff\Service\XliffFileProvider::class);
    }

    /**
     * @test
     */
    public function returnsTranslatedLabelWhenOriginalLabelProvided()
    {
        $mockFile = $this->getMockBuilder(I18n\Xliff\Model\FileAdapter::class)
            ->setConstructorArgs([[], $this->sampleLocale])
            ->getMock();
        $mockFile->expects($this->once())
            ->method('getTargetBySource')
            ->with('bar', 0)
            ->will($this->returnValue('baz'));

        $this->mockFileProvider->expects($this->once())
            ->method('getFile')
            ->with($this->sampleFileIdentifier, $this->sampleLocale)
            ->will($this->returnValue($mockFile));

        $this->mockPluralsReader->expects($this->once())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([
                I18n\Cldr\Reader\PluralsReader::RULE_ONE,
                I18n\Cldr\Reader\PluralsReader::RULE_OTHER
            ]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $this->inject($translationProvider, 'pluralsReader', $this->mockPluralsReader);
        $this->inject($translationProvider, 'fileProvider', $this->mockFileProvider);

        $result = $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);

        $this->assertEquals('baz', $result);
    }

    /**
     * @test
     */
    public function returnsTranslatedLabelWhenLabelIdProvided()
    {
        $mockFile = $this->getMockBuilder(I18n\Xliff\Model\FileAdapter::class)
            ->setConstructorArgs([[], $this->sampleLocale])
            ->getMock();
        $mockFile->expects($this->once())
            ->method('getTargetByTransUnitId')
            ->with('bar', 1)
            ->will($this->returnValue('baz'));

        $this->mockFileProvider->expects($this->once())
            ->method('getFile')
            ->with($this->sampleFileIdentifier, $this->sampleLocale)
            ->will($this->returnValue($mockFile));

        $this->mockPluralsReader->expects($this->once())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([
                I18n\Cldr\Reader\PluralsReader::RULE_ONE,
                I18n\Cldr\Reader\PluralsReader::RULE_OTHER
            ]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $this->inject($translationProvider, 'pluralsReader', $this->mockPluralsReader);
        $this->inject($translationProvider, 'fileProvider', $this->mockFileProvider);

        $result = $translationProvider->getTranslationById('bar', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_OTHER, $this->sampleSourceName, $this->samplePackageKey);
        $this->assertEquals('baz', $result);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByOriginalLabelThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->mockPluralsReader->expects($this->any())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([
                I18n\Cldr\Reader\PluralsReader::RULE_ONE,
                I18n\Cldr\Reader\PluralsReader::RULE_OTHER
            ]));

        $this->mockFileProvider->expects($this->any())
            ->method('getMergedFileData')
            ->with($this->sampleLocale)
            ->will($this->returnValue([
                I18n\Cldr\Reader\PluralsReader::RULE_ONE,
                I18n\Cldr\Reader\PluralsReader::RULE_OTHER
            ]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $this->inject($translationProvider, 'pluralsReader', $this->mockPluralsReader);
        $this->inject($translationProvider, 'fileProvider', $this->mockFileProvider);

        $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByIdThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->mockPluralsReader->expects($this->any())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([
                I18n\Cldr\Reader\PluralsReader::RULE_ONE,
                I18n\Cldr\Reader\PluralsReader::RULE_OTHER
            ]));

        $this->mockFileProvider->expects($this->any())
            ->method('getMergedFileData')
            ->with($this->sampleLocale, $this->samplePackageKey . ':' . $this->sampleSourceName)
            ->will($this->returnValue([
                'sourceLocale' => $this->sampleLocale,
                'fileIdentifier' => $this->samplePackageKey . ':' . $this->sampleSourceName,
                'translationUnits' => [
                    'key1' => [
                        [
                            'source' => 'Source string',
                            'target' => 'Übersetzte Zeichenkette',
                        ]
                    ]
                ]
            ]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $this->inject($translationProvider, 'pluralsReader', $this->mockPluralsReader);
        $this->inject($translationProvider, 'fileProvider', $this->mockFileProvider);

        $translationProvider->getTranslationById('key1', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }
}
