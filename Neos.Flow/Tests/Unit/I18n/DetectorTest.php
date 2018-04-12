<?php
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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the Locale Detector
 */
class DetectorTest extends UnitTestCase
{
    /**
     * @var I18n\Detector
     */
    protected $detector;

    /**
     * @return void
     */
    public function setUp()
    {
        $findBestMatchingLocaleCallback = function () {
            $args = func_get_args();
            $localeIdentifier = (string)$args[0];

            if (in_array($localeIdentifier, ['en_US_POSIX', 'en_Shaw'])) {
                return new I18n\Locale('en');
            } elseif ($localeIdentifier === 'en_GB') {
                return new I18n\Locale('en_GB');
            } elseif ($localeIdentifier === 'sr_RS') {
                return new I18n\Locale('sr');
            } else {
                return null;
            }
        };

        $mockLocaleCollection = $this->createMock(I18n\LocaleCollection::class);
        $mockLocaleCollection->expects($this->any())->method('findBestMatchingLocale')->will($this->returnCallback($findBestMatchingLocaleCallback));

        $mockLocalizationService = $this->createMock(I18n\Service::class);
        $mockLocalizationService->expects($this->any())->method('getConfiguration')->will($this->returnValue(new I18n\Configuration('sv_SE')));

        $this->detector = $this->getAccessibleMock(I18n\Detector::class, ['dummy']);
        $this->detector->_set('localeBasePath', 'vfs://Foo/');
        $this->detector->injectLocaleCollection($mockLocaleCollection);
        $this->detector->injectLocalizationService($mockLocalizationService);
    }

    /**
     * Data provider with valid Accept-Language headers and expected results.
     *
     * @return array
     */
    public function sampleHttpAcceptLanguageHeaders()
    {
        return [
            ['pl, en-gb;q=0.8, en;q=0.7', new I18n\Locale('en_GB')],
            ['de, *;q=0.8', new I18n\Locale('sv_SE')],
            ['pl, de;q=0.5, sr-rs;q=0.1', new I18n\Locale('sr')],
        ];
    }

    /**
     * @test
     * @dataProvider sampleHttpAcceptLanguageHeaders
     */
    public function detectingBestMatchingLocaleFromHttpAcceptLanguageHeaderWorksCorrectly($acceptLanguageHeader, $expectedResult)
    {
        $locale = $this->detector->detectLocaleFromHttpHeader($acceptLanguageHeader);
        $this->assertEquals($expectedResult, $locale);
    }

    /**
     * Data provider with valid locale identifiers (tags) and expected results.
     *
     * @return array
     */
    public function sampleLocaleIdentifiers()
    {
        return [
            ['en_GB', new I18n\Locale('en_GB')],
            ['en_US_POSIX', new I18n\Locale('en')],
            ['en_Shaw', new I18n\Locale('en')],
        ];
    }

    /**
     * @test
     * @dataProvider sampleLocaleIdentifiers
     */
    public function detectingBestMatchingLocaleFromLocaleIdentifierWorksCorrectly($localeIdentifier, $expectedResult)
    {
        $locale = $this->detector->detectLocaleFromLocaleTag($localeIdentifier);
        $this->assertEquals($expectedResult, $locale);
    }
}
