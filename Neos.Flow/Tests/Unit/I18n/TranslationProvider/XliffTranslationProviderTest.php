<?php
namespace Neos\Flow\Tests\Unit\I18n\TranslationProvider;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the XliffTranslationProvider
 */
class XliffTranslationProviderTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $samplePackageKey;

    /**
     * @var string
     */
    protected $sampleSourceName;

    /**
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * @var I18n\Cldr\Reader\PluralsReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPluralsReader;

    /**
     * @var I18n\Xliff\Service\XliffFileProvider|\PHPUnit_Framework_MockObject_MockObject $mockFileProvider
     */
    protected $mockFileProvider;

    /**
     * @var array
     */
    protected $mockParsedXliffFile;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->samplePackageKey = 'Neos.Flow';
        $this->sampleSourceName = 'Foo';
        $this->sampleLocale = new I18n\Locale('en_GB');

        $mockParsedXliffData = require(__DIR__ . '/../Fixtures/MockParsedXliffData.php');
        $this->mockParsedXliffFile = $mockParsedXliffData[0];

        $this->mockPluralsReader = $this->createMock(I18n\Cldr\Reader\PluralsReader::class);
        $this->mockFileProvider = $this->createMock(I18n\Xliff\Service\XliffFileProvider::class);
    }

    /**
     * @test
     */
    public function returnsTranslatedLabelWhenOriginalLabelProvided()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, $this->sampleLocale);
        $this->mockFileProvider->expects($this->once())
            ->method('getFile')
            ->with($this->samplePackageKey . ':' . $this->sampleSourceName, $this->sampleLocale)
            ->willReturn($fileAdapter);

        $this->mockPluralsReader->expects($this->any())->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->injectFileProvider($this->mockFileProvider);

        $result = $translationProvider->getTranslationByOriginalLabel('Source string', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
        $this->assertEquals('Übersetzte Zeichenkette', $result);
    }

    /**
     * @test
     */
    public function returnsTranslatedLabelWhenLabelIdProvided()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, $this->sampleLocale);
        $this->mockFileProvider->expects($this->once())
            ->method('getFile')
            ->with($this->samplePackageKey . ':' . $this->sampleSourceName, $this->sampleLocale)
            ->willReturn($fileAdapter);

        $this->mockPluralsReader->expects($this->any())->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->injectFileProvider($this->mockFileProvider);

        $result = $translationProvider->getTranslationById('key1', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
        $this->assertEquals('Übersetzte Zeichenkette', $result);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByOriginalLabelThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->mockPluralsReader->expects($this->any())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByIdThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->mockPluralsReader->expects($this->any())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will($this->returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationById('bar', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }
}
