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
    public function setUp()
    {
        $this->sampleLocale = new I18n\Locale('en');
    }

    /**
     * @test
     */
    public function formatIsCorrectlyReadFromCldr()
    {
        $mockModel = $this->createMock(I18n\Cldr\CldrModel::class, [], [[]]);
        $mockModel->expects($this->once())->method('getElement')->with('numbers/decimalFormats/decimalFormatLength/decimalFormat/pattern')->will($this->returnValue('mockFormatString'));

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModelForLocale')->with($this->sampleLocale)->will($this->returnValue($mockModel));

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->at(0))->method('has')->with('parsedFormats')->will($this->returnValue(true));
        $mockCache->expects($this->at(1))->method('has')->with('parsedFormatsIndices')->will($this->returnValue(true));
        $mockCache->expects($this->at(2))->method('has')->with('localizedSymbols')->will($this->returnValue(true));
        $mockCache->expects($this->at(3))->method('get')->with('parsedFormats')->will($this->returnValue([]));
        $mockCache->expects($this->at(4))->method('get')->with('parsedFormatsIndices')->will($this->returnValue([]));
        $mockCache->expects($this->at(5))->method('get')->with('localizedSymbols')->will($this->returnValue([]));
        $mockCache->expects($this->at(6))->method('set')->with('parsedFormats');
        $mockCache->expects($this->at(7))->method('set')->with('parsedFormatsIndices');
        $mockCache->expects($this->at(8))->method('set')->with('localizedSymbols');

        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, ['parseFormat']);
        $reader->expects($this->once())->method('parseFormat')->with('mockFormatString')->will($this->returnValue('mockParsedFormat'));
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL);
        $this->assertEquals('mockParsedFormat', $result);

        $reader->shutdownObject();
    }

    /**
     * Data provider with valid format strings and expected results.
     *
     * @return array
     */
    public function formatStringsAndParsedFormats()
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
     */
    public function formatStringsAreParsedCorrectly($format, array $expectedResult)
    {
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, ['dummy']);

        $result = $reader->_call('parseFormat', $format);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with formats not supported by current implementation of
     * NumbersReader.
     *
     * @return array
     */
    public function unsupportedFormats()
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
     * @expectedException \Neos\Flow\I18n\Cldr\Reader\Exception\UnsupportedNumberFormatException
     */
    public function throwsExceptionWhenUnsupportedFormatsEncountered($format)
    {
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\NumbersReader::class, ['dummy']);

        $reader->_call('parseFormat', $format);
    }
}
