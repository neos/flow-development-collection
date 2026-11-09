<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n\Cldr\Reader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\I18n;
use Neos\Flow\I18n\Cldr\CldrModel;
use Neos\Flow\I18n\Cldr\CldrRepository;
use Neos\Flow\I18n\Cldr\Reader\Exception\UnsupportedNumberFormatException;
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the NumbersReader
 */
final class NumbersReaderTest extends UnitTestCase
{
    /**
     * Dummy locale used in methods where locale is needed.
     *
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * A template array of parsed format. Used as a base in order to not repeat
     * same fields everywhere.
     *
     * @var array
     */
    protected static $templateFormat = [
        'positivePrefix' => '',
        'positiveSuffix' => '',
        'negativePrefix' => '-',
        'negativeSuffix' => '',

        'multiplier' => 1,

        'minDecimalDigits' => 0,
        'maxDecimalDigits' => 0,

        'minIntegerDigits' => 1,

        'primaryGroupingSize' => 0,
        'secondaryGroupingSize' => 0,

        'rounding' => 0,
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sampleLocale = new Locale('en');
    }

    #[Test]
    public function formatIsCorrectlyReadFromCldr(): void
    {
        $mockModel = $this->createMock(CldrModel::class);
        $mockModel->expects($this->once())->method('getElement')->with('numbers/decimalFormats/decimalFormatLength/decimalFormat/pattern')->willReturn('mockFormatString');

        $mockRepository = $this->createMock(CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModelForLocale')->with($this->sampleLocale)->willReturn($mockModel);

        $mockCache = $this->createMock(VariableFrontend::class);
        $matcher = self::atLeast(3);
        $mockCache->expects($matcher)->method('has')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('parsedFormats', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('parsedFormatsIndices', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('localizedSymbols', $parameters[0]);
            }
            return true;
        });
        $matcher = self::atLeast(3);
        $mockCache->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('parsedFormats', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('parsedFormatsIndices', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('localizedSymbols', $parameters[0]);
            }
            return [];
        });
        $matcher = self::atLeast(3);
        $mockCache->expects($matcher)->method('set')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('parsedFormats', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('parsedFormatsIndices', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('localizedSymbols', $parameters[0]);
            }
        });

        /** @var MockObject|I18n\Cldr\Reader\NumbersReader $reader */
        $reader = $this->getAccessibleMock(NumbersReader::class, ['parseFormat']);
        $reader->expects($this->once())->method('parseFormat')->with('mockFormatString')->willReturn(['mockParsedFormat']);
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, NumbersReader::FORMAT_TYPE_DECIMAL);
        self::assertEquals(['mockParsedFormat'], $result);

        $reader->shutdownObject();
    }

    /**
     * Data provider with valid format strings and expected results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function formatStringsAndParsedFormats(): \Iterator
    {
        yield ['#,##0.###', array_merge(self::$templateFormat, ['maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3])];
        yield ['#,##,##0%', array_merge(self::$templateFormat, ['positiveSuffix' => '%', 'negativeSuffix' => '%', 'multiplier' => 100, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 2])];
        yield ['¤ #,##0.00;¤ #,##0.00-', array_merge(self::$templateFormat, ['positivePrefix' => '¤ ', 'negativePrefix' => '¤ ', 'negativeSuffix' => '-', 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3])];
        yield ['#,##0.05', array_merge(self::$templateFormat, ['minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05])];
    }

    /**
     * @param string $format
     * @param array $expectedResult
     */
    #[DataProvider('formatStringsAndParsedFormats')]
    #[Test]
    public function formatStringsAreParsedCorrectly(string $format, array $expectedResult): void
    {
        $reader = $this->getAccessibleMock(NumbersReader::class, []);

        $result = $reader->_call('parseFormat', $format);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with formats not supported by current implementation of
     * NumbersReader.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function unsupportedFormats(): \Iterator
    {
        yield ['0.###E0'];
        yield ['@##'];
        yield ['* #0'];
        yield ['\'#\'##'];
    }

    /**
     * @param string $format
     */
    #[DataProvider('unsupportedFormats')]
    #[Test]
    public function throwsExceptionWhenUnsupportedFormatsEncountered(string $format): void
    {
        $this->expectException(UnsupportedNumberFormatException::class);
        $reader = $this->getAccessibleMock(NumbersReader::class, []);

        $reader->_call('parseFormat', $format);
    }
}
