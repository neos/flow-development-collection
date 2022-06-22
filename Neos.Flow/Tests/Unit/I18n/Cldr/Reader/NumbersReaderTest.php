<?php
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
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the NumbersReader
 */
class NumbersReaderTest extends UnitTestCase
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
    protected $templateFormat = [
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
        $this->sampleLocale = new I18n\Locale('en');
    }

    /**
     * @test
     */
    public function formatIsCorrectlyReadFromCldr(): void
    {
        $mockModel = $this->createMock(I18n\Cldr\CldrModel::class);
        $mockModel->expects(self::once())->method('getElement')->with('numbers/decimalFormats/decimalFormatLength/decimalFormat/pattern')->willReturn('mockFormatString');

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects(self::once())->method('getModelForLocale')->with($this->sampleLocale)->willReturn($mockModel);

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects(self::atLeast(3))->method('has')->withConsecutive(['parsedFormats'], ['parsedFormatsIndices'], ['localizedSymbols'])->willReturn(true);
        $mockCache->expects(self::atLeast(3))->method('get')->withConsecutive(['parsedFormats'], ['parsedFormatsIndices'], ['localizedSymbols'])->willReturn([]);
        $mockCache->expects(self::atLeast(3))->method('set')->withConsecutive(['parsedFormats'], ['parsedFormatsIndices'], ['localizedSymbols']);

        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, ['parseFormat']);
        $reader->expects(self::once())->method('parseFormat')->with('mockFormatString')->willReturn(['mockParsedFormat']);
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL);
        self::assertEquals(['mockParsedFormat'], $result);

        $reader->shutdownObject();
    }

    /**
     * Data provider with valid format strings and expected results.
     *
     * @return array
     */
    public function formatStringsAndParsedFormats(): array
    {
        return [
            ['#,##0.###', array_merge($this->templateFormat, ['maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3])],
            ['#,##,##0%', array_merge($this->templateFormat, ['positiveSuffix' => '%', 'negativeSuffix' => '%', 'multiplier' => 100, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 2])],
            ['¤ #,##0.00;¤ #,##0.00-', array_merge($this->templateFormat, ['positivePrefix' => '¤ ', 'negativePrefix' => '¤ ', 'negativeSuffix' => '-', 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3])],
            ['#,##0.05', array_merge($this->templateFormat, ['minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05])],
        ];
    }

    /**
     * @test
     * @dataProvider formatStringsAndParsedFormats
     * @param string $format
     * @param array $expectedResult
     */
    public function formatStringsAreParsedCorrectly(string $format, array $expectedResult): void
    {
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, ['dummy']);

        $result = $reader->_call('parseFormat', $format);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with formats not supported by current implementation of
     * NumbersReader.
     *
     * @return array
     */
    public function unsupportedFormats(): array
    {
        return [
            ['0.###E0'],
            ['@##'],
            ['* #0'],
            ['\'#\'##'],
        ];
    }

    /**
     * @test
     * @dataProvider unsupportedFormats
     * @param string $format
     */
    public function throwsExceptionWhenUnsupportedFormatsEncountered(string $format): void
    {
        $this->expectException(I18n\Cldr\Reader\Exception\UnsupportedNumberFormatException::class);
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, ['dummy']);

        $reader->_call('parseFormat', $format);
    }
}
