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
use Neos\Flow\I18n\Cldr\Reader\DatesReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the DatesReader
 */
final class DatesReaderTest extends UnitTestCase
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
        $this->sampleLocale = new Locale('en');
    }

    /**
     * Setting cache expectations is partially same for many tests, so it's been
     * extracted to this method.
     */
    public function createCacheExpectations(MockObject $mockCache): void
    {
        $matcher = self::atLeast(3);
        $mockCache->expects($matcher)->method('has')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('parsedFormats', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('parsedFormatsIndices', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('localizedLiterals', $parameters[0]);
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
                $this->assertSame('localizedLiterals', $parameters[0]);
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
                $this->assertSame('localizedLiterals', $parameters[0]);
            }
        });
    }

    #[Test]
    public function formatIsCorrectlyReadFromCldr(): void
    {
        $mockModel = $this->getAccessibleMock(CldrModel::class, ['getRawArray', 'getElement'], [[]]);
        $mockModel->expects($this->once())->method('getElement')->with('dates/calendars/calendar[@type="gregorian"]/dateFormats/dateFormatLength[@type="medium"]/dateFormat/pattern')->willReturn(('mockFormatString'));

        $mockRepository = $this->createMock(CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModelForLocale')->with($this->sampleLocale)->willReturn(($mockModel));

        $mockCache = $this->createMock(VariableFrontend::class);
        $this->createCacheExpectations($mockCache);

        /** @var MockObject|I18n\Cldr\Reader\DatesReader $reader */
        $reader = $this->getAccessibleMock(DatesReader::class, ['parseFormat']);
        $reader->expects($this->once())->method('parseFormat')->with('mockFormatString')->willReturn((['mockParsedFormat']));
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, DatesReader::FORMAT_TYPE_DATE, DatesReader::FORMAT_LENGTH_DEFAULT);
        self::assertEquals(['mockParsedFormat'], $result);

        $reader->shutdownObject();
    }

    #[Test]
    public function dateTimeFormatIsParsedCorrectly(): void
    {
        $mockModel = $this->getAccessibleMock(CldrModel::class, ['getElement'], [[]]);
        $matcher = self::exactly(3);
        $mockModel->expects(
            $matcher
        )->method('getElement')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('dates/calendars/calendar[@type="gregorian"]/dateTimeFormats/dateTimeFormatLength[@type="full"]/dateTimeFormat/pattern', $parameters[0]);
                return 'foo {0} {1} bar';
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('dates/calendars/calendar[@type="gregorian"]/dateFormats/dateFormatLength[@type="full"]/dateFormat/pattern', $parameters[0]);
                return 'dMy';
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('dates/calendars/calendar[@type="gregorian"]/timeFormats/timeFormatLength[@type="full"]/timeFormat/pattern', $parameters[0]);
                return 'hms';
            }
        });

        $mockRepository = $this->createMock(CldrRepository::class);
        $mockRepository->expects($this->exactly(3))->method('getModelForLocale')->with($this->sampleLocale)->willReturn(($mockModel));

        $mockCache = $this->createMock(VariableFrontend::class);
        $this->createCacheExpectations($mockCache);

        $reader = new DatesReader();
        $reader->injectCldrRepository($mockRepository);
        $reader->injectCache($mockCache);
        $reader->initializeObject();

        $result = $reader->parseFormatFromCldr($this->sampleLocale, DatesReader::FORMAT_TYPE_DATETIME, DatesReader::FORMAT_LENGTH_FULL);
        self::assertSame([['foo '], 'h', 'm', 's', [' '], 'd', 'M', 'y', [' bar']], $result);
        $reader->shutdownObject();
    }

    #[Test]
    public function localizedLiteralsAreCorrectlyReadFromCldr(): void
    {
        $getRawArrayCallback = static function () {
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

        $mockModel = $this->getAccessibleMock(CldrModel::class, ['getRawArray'], [[]]);
        $mockModel->expects($this->exactly(5))->method('getRawArray')->willReturnCallback($getRawArrayCallback);

        $mockRepository = $this->createMock(CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModelForLocale')->with($this->sampleLocale)->willReturn(($mockModel));

        $mockCache = $this->createMock(VariableFrontend::class);
        $this->createCacheExpectations($mockCache);

        $reader = new DatesReader();
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
     * @return \Iterator<(int | string), mixed>
     */
    public static function formatStringsAndParsedFormats(): \Iterator
    {
        yield ['yyyy.MM.dd G', ['yyyy', ['.'], 'MM', ['.'], 'dd', [' '], 'G']];
        yield ['HH:mm:ss zzz', ['HH', [':'], 'mm', [':'], 'ss', [' '], 'zzz']];
        yield ['EEE, MMM d, \'\'yy', ['EEE', [','], [' '], 'MMM', [' '], 'd', [','], [' '], ['\''], 'yy']];
        yield ['hh \'o\'\'clock\' a, zzzz', ['hh', [' '], ['o'], ['\''], ['clock'], [' '], 'a', [','], [' '], 'zzzz']];
        yield ['QQyyLLLLDFEEEE', ['QQ', 'yy', 'LLLL', 'D', 'F', 'EEEE']];
        yield ['QQQMMMMMEEEEEwk', ['QQQ', 'MMMMM', 'EEEEE', 'w', 'k']];
        yield ['GGGGGKSWqqqqGGGGV', ['GGGGG', 'K', 'S', 'W', 'qqqq', 'GGGG', 'V']];
        yield ['QQyyLLLLDFEEEEccc', ['QQ', 'yy', 'LLLL', 'D', 'F', 'EEEE', 'ccc']];
    }

    #[DataProvider('formatStringsAndParsedFormats')]
    #[Test]
    public function formatStringsAreParsedCorrectly($format, $expectedResult): void
    {
        $reader = $this->getAccessibleMock(DatesReader::class, []);

        $result = $reader->_call('parseFormat', $format);
        self::assertEquals($expectedResult, $result);
    }
}
