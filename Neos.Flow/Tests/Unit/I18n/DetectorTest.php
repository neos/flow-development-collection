<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n;

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
use Neos\Flow\I18n\Configuration;
use Neos\Flow\I18n\Detector;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\LocaleCollection;
use Neos\Flow\I18n\Service;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Locale Detector
 */
final class DetectorTest extends UnitTestCase
{
    /**
     * @var I18n\Detector
     */
    protected $detector;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $findBestMatchingLocaleCallback = function () {
            $args = func_get_args();
            $localeIdentifier = (string)$args[0];

            if (in_array($localeIdentifier, ['en_US_POSIX', 'en_Shaw'])) {
                return new Locale('en');
            } elseif ($localeIdentifier === 'en_GB') {
                return new Locale('en_GB');
            } elseif ($localeIdentifier === 'sr_RS') {
                return new Locale('sr');
            } else {
                return null;
            }
        };

        $mockLocaleCollection = $this->createMock(LocaleCollection::class);
        $mockLocaleCollection->method('findBestMatchingLocale')->willReturnCallback($findBestMatchingLocaleCallback);

        $mockLocalizationService = $this->createMock(Service::class);
        $mockLocalizationService->method('getConfiguration')->willReturn((new Configuration('sv_SE')));

        $this->detector = $this->getAccessibleMock(Detector::class, []);
        $this->detector->_set('localeBasePath', 'vfs://Foo/');
        $this->detector->injectLocaleCollection($mockLocaleCollection);
        $this->detector->injectLocalizationService($mockLocalizationService);
    }

    /**
     * Data provider with valid Accept-Language headers and expected results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleHttpAcceptLanguageHeaders(): \Iterator
    {
        yield ['pl, en-gb;q=0.8, en;q=0.7', new Locale('en_GB')];
        yield ['de, *;q=0.8', new Locale('sv_SE')];
        yield ['pl, de;q=0.5, sr-rs;q=0.1', new Locale('sr')];
    }

    #[DataProvider('sampleHttpAcceptLanguageHeaders')]
    #[Test]
    public function detectingBestMatchingLocaleFromHttpAcceptLanguageHeaderWorksCorrectly($acceptLanguageHeader, $expectedResult)
    {
        $locale = $this->detector->detectLocaleFromHttpHeader($acceptLanguageHeader);
        self::assertEquals($expectedResult, $locale);
    }

    /**
     * Data provider with valid locale identifiers (tags) and expected results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleLocaleIdentifiers(): \Iterator
    {
        yield ['en_GB', new Locale('en_GB')];
        yield ['en_US_POSIX', new Locale('en')];
        yield ['en_Shaw', new Locale('en')];
    }

    #[DataProvider('sampleLocaleIdentifiers')]
    #[Test]
    public function detectingBestMatchingLocaleFromLocaleIdentifierWorksCorrectly($localeIdentifier, $expectedResult)
    {
        $locale = $this->detector->detectLocaleFromLocaleTag($localeIdentifier);
        self::assertEquals($expectedResult, $locale);
    }
}
