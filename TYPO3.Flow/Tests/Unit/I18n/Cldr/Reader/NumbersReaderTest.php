<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Cldr\Reader;

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
 * Testcase for the NumbersReader
 *
 */
class NumbersReaderTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Dummy locale used in methods where locale is needed.
     *
     * @var \TYPO3\Flow\I18n\Locale
     */
    protected $sampleLocale;

    /**
     * A template array of parsed format. Used as a base in order to not repeat
     * same fields everywhere.
     *
     * @var array
     */
    protected $templateFormat = array(
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
    );

    /**
     * @return void
     */
    public function setUp()
    {
        $this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en');
    }

    /**
     * @test
     */
    public function formatIsCorrectlyReadFromCldr()
    {
        $mockModel = $this->createMock(\TYPO3\Flow\I18n\Cldr\CldrModel::class, array(), array(array()));
        $mockModel->expects($this->once())->method('getElement')->with('numbers/decimalFormats/decimalFormatLength/decimalFormat/pattern')->will($this->returnValue('mockFormatString'));

        $mockRepository = $this->createMock(\TYPO3\Flow\I18n\Cldr\CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModelForLocale')->with($this->sampleLocale)->will($this->returnValue($mockModel));

        $mockCache = $this->getMockBuilder(\TYPO3\Flow\Cache\Frontend\VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->at(0))->method('has')->with('parsedFormats')->will($this->returnValue(true));
        $mockCache->expects($this->at(1))->method('has')->with('parsedFormatsIndices')->will($this->returnValue(true));
        $mockCache->expects($this->at(2))->method('has')->with('localizedSymbols')->will($this->returnValue(true));
        $mockCache->expects($this->at(3))->method('get')->with('parsedFormats')->will($this->returnValue(array()));
        $mockCache->expects($this->at(4))->method('get')->with('parsedFormatsIndices')->will($this->returnValue(array()));
        $mockCache->expects($this->at(5))->method('get')->with('localizedSymbols')->will($this->returnValue(array()));
        $mockCache->expects($this->at(6))->method('set')->with('parsedFormats');
        $mockCache->expects($this->at(7))->method('set')->with('parsedFormatsIndices');
        $mockCache->expects($this->at(8))->method('set')->with('localizedSymbols');

        $reader = $this->getAccessibleMock(\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::class, array('parseFormat'));
        $reader->expects($this->once())->method('parseFormat')->with('mockFormatString')->will($this->returnValue('mockParsedFormat'));
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL);
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
        return array(
            array('#,##0.###', array_merge($this->templateFormat, array('maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3))),
            array('#,##,##0%', array_merge($this->templateFormat, array('positiveSuffix' => '%', 'negativeSuffix' => '%', 'multiplier' => 100, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 2))),
            array('¤ #,##0.00;¤ #,##0.00-', array_merge($this->templateFormat, array('positivePrefix' => '¤ ', 'negativePrefix' => '¤ ', 'negativeSuffix' => '-', 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3))),
            array('#,##0.05', array_merge($this->templateFormat, array('minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05))),
        );
    }

    /**
     * @test
     * @dataProvider formatStringsAndParsedFormats
     */
    public function formatStringsAreParsedCorrectly($format, array $expectedResult)
    {
        $reader = $this->getAccessibleMock(\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::class, array('dummy'));

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
        return array(
            array('0.###E0'),
            array('@##'),
            array('* #0'),
            array('\'#\'##'),
        );
    }

    /**
     * @test
     * @dataProvider unsupportedFormats
     * @expectedException \TYPO3\Flow\I18n\Cldr\Reader\Exception\UnsupportedNumberFormatException
     */
    public function throwsExceptionWhenUnsupportedFormatsEncountered($format)
    {
        $reader = $this->getAccessibleMock(\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::class, array('dummy'));

        $reader->_call('parseFormat', $format);
    }
}
