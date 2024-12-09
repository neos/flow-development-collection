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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;

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
        $mockModel->expects($this->once())->method('getElement')->with('numbers/decimalFormats/decimalFormatLength/decimalFormat/pattern')->willReturn('mockFormatString');

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModelForLocale')->with($this->sampleLocale)->willReturn($mockModel);

        $callback = function (InvocationOrder $matcher, mixed $returnValue) {
            return function (string $id) use ($matcher, $returnValue) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('parsedFormats', $id);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('parsedFormatsIndices', $id);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertSame('localizedSymbols', $id);
                }

                return $returnValue;
            };
        };

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $matcher = $this->atLeast(3);
        $mockCache->expects($matcher)->method('has')
            ->willReturnCallback($callback($matcher, true));

        $matcher = $this->atLeast(3);
        $mockCache->expects($matcher)->method('get')
            ->willReturnCallback($callback($matcher, []));

        $matcher = $this->atLeast(3);
        $mockCache->expects($matcher)->method('set')
            ->willReturnCallback($callback($matcher, null));

        /** @var MockObject|I18n\Cldr\Reader\NumbersReader $reader */
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, ['parseFormat']);
        $reader->expects($this->once())->method('parseFormat')->with('mockFormatString')->willReturn(['mockParsedFormat']);
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
    public static function formatStringsAndParsedFormats(): array
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
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, []);

        $result = $reader->_call('parseFormat', $format);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with formats not supported by current implementation of
     * NumbersReader.
     *
     * @return array
     */
    public static function unsupportedFormats(): array
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
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, []);

        $reader->_call('parseFormat', $format);
    }
}
