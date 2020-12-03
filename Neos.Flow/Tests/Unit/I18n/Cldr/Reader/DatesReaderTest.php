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
use Neos\Flow\I18n;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the DatesReader
 */
class DatesReaderTest extends UnitTestCase
{
    /**
     * Dummy locale used in methods where locale is needed.
     *
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sampleLocale = new I18n\Locale('en');
    }

    /**
     * Setting cache expectations is partially same for many tests, so it's been
     * extracted to this method.
     *
     * @param MockObject $mockCache
     * @return array
     */
    public function createCacheExpectations(MockObject $mockCache)
    {
        $mockCache->expects(self::atLeast(3))->method('has')->withConsecutive(['parsedFormats'], ['parsedFormatsIndices'], ['localizedLiterals'])->willReturn(true);
        $mockCache->expects(self::atLeast(3))->method('get')->withConsecutive(['parsedFormats'], ['parsedFormatsIndices'], ['localizedLiterals'])->willReturn([]);
        $mockCache->expects(self::atLeast(3))->method('set')->withConsecutive(['parsedFormats'], ['parsedFormatsIndices'], ['localizedLiterals']);
    }

    /**
     * @test
     */
    public function formatIsCorrectlyReadFromCldr()
    {
        $mockModel = $this->getAccessibleMock(I18n\Cldr\CldrModel::class, ['getRawArray', 'getElement'], [[]]);
        $mockModel->expects(self::once())->method('getElement')->with('dates/calendars/calendar[@type="gregorian"]/dateFormats/dateFormatLength[@type="medium"]/dateFormat/pattern')->will(self::returnValue('mockFormatString'));

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects(self::once())->method('getModelForLocale')->with($this->sampleLocale)->will(self::returnValue($mockModel));

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $this->createCacheExpectations($mockCache);

        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\DatesReader::class, ['parseFormat']);
        $reader->expects(self::once())->method('parseFormat')->with('mockFormatString')->will(self::returnValue(['mockParsedFormat']));
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT);
        self::assertEquals(['mockParsedFormat'], $result);

        $reader->shutdownObject();
    }

    /**
     * @test
     */
    public function dateTimeFormatIsParsedCorrectly()
    {
        $mockModel = $this->getAccessibleMock(I18n\Cldr\CldrModel::class, ['getElement'], [[]]);
        $mockModel->expects(
            self::exactly(3)
        )->method('getElement')->withConsecutive(
            ['dates/calendars/calendar[@type="gregorian"]/dateTimeFormats/dateTimeFormatLength[@type="full"]/dateTimeFormat/pattern'],
            ['dates/calendars/calendar[@type="gregorian"]/dateFormats/dateFormatLength[@type="full"]/dateFormat/pattern'],
            ['dates/calendars/calendar[@type="gregorian"]/timeFormats/timeFormatLength[@type="full"]/timeFormat/pattern']
        )->willReturnOnConsecutiveCalls(
            'foo {0} {1} bar',
            'dMy',
            'hms'
        );

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects(self::exactly(3))->method('getModelForLocale')->with($this->sampleLocale)->will(self::returnValue($mockModel));

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $this->createCacheExpectations($mockCache);

        $reader = new I18n\Cldr\Reader\DatesReader();
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME, I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL);
        self::assertEquals([['foo '], 'h', 'm', 's', [' '], 'd', 'M', 'y', [' bar']], $result);
        $reader->shutdownObject();
    }

    /**
     * @test
     */
    public function localizedLiteralsAreCorrectlyReadFromCldr()
    {
        $getRawArrayCallback = function () {
            $args = func_get_args();
            $mockDatesCldrData = require(__DIR__ . '/../../Fixtures/MockDatesParsedCldrData.php');

            $lastPartOfPath = substr($args[0], strrpos($args[0], '/') + 1);
            // Eras have different XML structure than other literals so they have to be handled differently
            if ($lastPartOfPath === 'eras') {
                return $mockDatesCldrData['eras'];
            } else {
                return $mockDatesCldrData[$lastPartOfPath];
            }
        };

        $mockModel = $this->getAccessibleMock(I18n\Cldr\CldrModel::class, ['getRawArray'], [[]]);
        $mockModel->expects(self::exactly(5))->method('getRawArray')->will(self::returnCallBack($getRawArrayCallback));

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects(self::once())->method('getModelForLocale')->with($this->sampleLocale)->will(self::returnValue($mockModel));

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $this->createCacheExpectations($mockCache);

        $reader = new I18n\Cldr\Reader\DatesReader();
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->getLocalizedLiteralsForLocale($this->sampleLocale);
        self::assertEquals('January', $result['months']['format']['wide'][1]);
        self::assertEquals('Sat', $result['days']['format']['abbreviated']['sat']);
        self::assertEquals('1', $result['quarters']['format']['narrow'][1]);
        self::assertEquals('a.m.', $result['dayPeriods']['stand-alone']['wide']['am']);
        self::assertEquals('Anno Domini', $result['eras']['eraNames'][1]);

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
            ['yyyy.MM.dd G', ['yyyy', ['.'], 'MM', ['.'], 'dd', [' '], 'G']],
            ['HH:mm:ss zzz', ['HH', [':'], 'mm', [':'], 'ss', [' '], 'zzz']],
            ['EEE, MMM d, \'\'yy', ['EEE', [','], [' '], 'MMM', [' '], 'd', [','], [' '], ['\''], 'yy']],
            ['hh \'o\'\'clock\' a, zzzz', ['hh', [' '], ['o'], ['\''], ['clock'], [' '], 'a', [','], [' '], 'zzzz']],
            ['QQyyLLLLDFEEEE', ['QQ', 'yy', 'LLLL', 'D', 'F', 'EEEE']],
            ['QQQMMMMMEEEEEwk', ['QQQ', 'MMMMM', 'EEEEE', 'w', 'k']],
            ['GGGGGKSWqqqqGGGGV', ['GGGGG', 'K', 'S', 'W', 'qqqq', 'GGGG', 'V']],
        ];
    }

    /**
     * @test
     * @dataProvider formatStringsAndParsedFormats
     */
    public function formatStringsAreParsedCorrectly($format, $expectedResult)
    {
        $reader = $this->getAccessibleMock(I18n\Cldr\Reader\DatesReader::class, ['dummy']);

        $result = $reader->_call('parseFormat', $format);
        self::assertEquals($expectedResult, $result);
    }
}
