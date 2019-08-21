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
     * @var I18n\Cldr\Reader\PluralsReader|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPluralsReader;

    /**
     * @var I18n\Xliff\Service\XliffFileProvider|\PHPUnit\Framework\MockObject\MockObject $mockFileProvider
     */
    protected $mockFileProvider;

    /**
     * @var array
     */
    protected $mockParsedXliffFile;

    /**
     * @return void
     */
    protected function setUp(): void
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
        $this->mockFileProvider->expects(self::once())
            ->method('getFile')
            ->with($this->samplePackageKey . ':' . $this->sampleSourceName, $this->sampleLocale)
            ->willReturn($fileAdapter);

        $this->mockPluralsReader->expects(self::any())->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will(self::returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->injectFileProvider($this->mockFileProvider);

        $result = $translationProvider->getTranslationByOriginalLabel('Source string', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
        self::assertEquals('Übersetzte Zeichenkette', $result);
    }

    /**
     * @test
     */
    public function returnsTranslatedLabelWhenLabelIdProvided()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, $this->sampleLocale);
        $this->mockFileProvider->expects(self::once())
            ->method('getFile')
            ->with($this->samplePackageKey . ':' . $this->sampleSourceName, $this->sampleLocale)
            ->willReturn($fileAdapter);

        $this->mockPluralsReader->expects(self::any())->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will(self::returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->injectFileProvider($this->mockFileProvider);

        $result = $translationProvider->getTranslationById('key1', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
        self::assertEquals('Übersetzte Zeichenkette', $result);
    }

    /**
     * @test
     */
    public function getTranslationByOriginalLabelThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->expectException(I18n\TranslationProvider\Exception\InvalidPluralFormException::class);
        $this->mockPluralsReader->expects(self::any())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will(self::returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }

    /**
     * @test
     */
    public function getTranslationByIdThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->expectException(I18n\TranslationProvider\Exception\InvalidPluralFormException::class);
        $this->mockPluralsReader->expects(self::any())
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->will(self::returnValue([I18n\Cldr\Reader\PluralsReader::RULE_ONE, I18n\Cldr\Reader\PluralsReader::RULE_OTHER]));

        $translationProvider = new I18n\TranslationProvider\XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationById('bar', $this->sampleLocale, I18n\Cldr\Reader\PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }
}
