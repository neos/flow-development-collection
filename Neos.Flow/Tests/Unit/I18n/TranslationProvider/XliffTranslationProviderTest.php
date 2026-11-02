<?php

declare(strict_types=1);

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
use Neos\Flow\I18n\Cldr\Reader\PluralsReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException;
use Neos\Flow\I18n\TranslationProvider\XliffTranslationProvider;
use Neos\Flow\I18n\Xliff\Model\FileAdapter;
use Neos\Flow\I18n\Xliff\Service\XliffFileProvider;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the XliffTranslationProvider
 */
final class XliffTranslationProviderTest extends UnitTestCase
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
     * @var I18n\Cldr\Reader\PluralsReader|MockObject
     */
    protected $mockPluralsReader;

    /**
     * @var I18n\Xliff\Service\XliffFileProvider|MockObject $mockFileProvider
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
        $this->sampleLocale = new Locale('en_GB');

        $mockParsedXliffData = require(__DIR__ . '/../Fixtures/MockParsedXliffData.php');
        $this->mockParsedXliffFile = $mockParsedXliffData[0];

        $this->mockPluralsReader = $this->createMock(PluralsReader::class);
        $this->mockFileProvider = $this->createMock(XliffFileProvider::class);
    }

    #[Test]
    public function returnsTranslatedLabelWhenOriginalLabelProvided()
    {
        $fileAdapter = new FileAdapter($this->mockParsedXliffFile, $this->sampleLocale);
        $this->mockFileProvider->expects($this->once())
            ->method('getFile')
            ->with($this->samplePackageKey . ':' . $this->sampleSourceName, $this->sampleLocale)
            ->willReturn($fileAdapter);

        $this->mockPluralsReader->method('getPluralForms')
            ->with($this->sampleLocale)
            ->willReturn(([PluralsReader::RULE_ONE, PluralsReader::RULE_OTHER]));

        $translationProvider = new XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->injectFileProvider($this->mockFileProvider);

        $result = $translationProvider->getTranslationByOriginalLabel('Source string', $this->sampleLocale, PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
        self::assertEquals('Übersetzte Zeichenkette', $result);
    }

    #[Test]
    public function returnsTranslatedLabelWhenLabelIdProvided()
    {
        $fileAdapter = new FileAdapter($this->mockParsedXliffFile, $this->sampleLocale);
        $this->mockFileProvider->expects($this->once())
            ->method('getFile')
            ->with($this->samplePackageKey . ':' . $this->sampleSourceName, $this->sampleLocale)
            ->willReturn($fileAdapter);

        $this->mockPluralsReader->method('getPluralForms')
            ->with($this->sampleLocale)
            ->willReturn(([PluralsReader::RULE_ONE, PluralsReader::RULE_OTHER]));

        $translationProvider = new XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);
        $translationProvider->injectFileProvider($this->mockFileProvider);

        $result = $translationProvider->getTranslationById('key1', $this->sampleLocale, PluralsReader::RULE_ONE, $this->sampleSourceName, $this->samplePackageKey);
        self::assertEquals('Übersetzte Zeichenkette', $result);
    }

    #[Test]
    public function getTranslationByOriginalLabelThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->expectException(InvalidPluralFormException::class);
        $this->mockPluralsReader
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->willReturn(([PluralsReader::RULE_ONE, PluralsReader::RULE_OTHER]));

        $translationProvider = new XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationByOriginalLabel('bar', $this->sampleLocale, PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }

    #[Test]
    public function getTranslationByIdThrowsExceptionWhenInvalidPluralFormProvided()
    {
        $this->expectException(InvalidPluralFormException::class);
        $this->mockPluralsReader
            ->method('getPluralForms')
            ->with($this->sampleLocale)
            ->willReturn(([PluralsReader::RULE_ONE, PluralsReader::RULE_OTHER]));

        $translationProvider = new XliffTranslationProvider();
        $translationProvider->injectPluralsReader($this->mockPluralsReader);

        $translationProvider->getTranslationById('bar', $this->sampleLocale, PluralsReader::RULE_FEW, $this->sampleSourceName, $this->samplePackageKey);
    }
}
