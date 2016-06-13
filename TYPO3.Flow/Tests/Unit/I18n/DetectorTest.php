<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

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
 * Testcase for the Locale Detector
 *
 */
class DetectorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\I18n\Detector
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

            if (in_array($localeIdentifier, array('en_US_POSIX', 'en_Shaw'))) {
                return new \TYPO3\Flow\I18n\Locale('en');
            } elseif ($localeIdentifier === 'en_GB') {
                return new \TYPO3\Flow\I18n\Locale('en_GB');
            } elseif ($localeIdentifier === 'sr_RS') {
                return new \TYPO3\Flow\I18n\Locale('sr');
            } else {
                return null;
            }
        };

        $mockLocaleCollection = $this->createMock('TYPO3\Flow\I18n\LocaleCollection');
        $mockLocaleCollection->expects($this->any())->method('findBestMatchingLocale')->will($this->returnCallback($findBestMatchingLocaleCallback));

        $mockLocalizationService = $this->createMock('TYPO3\Flow\I18n\Service');
        $mockLocalizationService->expects($this->any())->method('getConfiguration')->will($this->returnValue(new \TYPO3\Flow\I18n\Configuration('sv_SE')));

        $this->detector = $this->getAccessibleMock('TYPO3\Flow\I18n\Detector', array('dummy'));
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
        return array(
            array('pl, en-gb;q=0.8, en;q=0.7', new \TYPO3\Flow\I18n\Locale('en_GB')),
            array('de, *;q=0.8', new \TYPO3\Flow\I18n\Locale('sv_SE')),
            array('pl, de;q=0.5, sr-rs;q=0.1', new \TYPO3\Flow\I18n\Locale('sr')),
        );
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
        return array(
            array('en_GB', new \TYPO3\Flow\I18n\Locale('en_GB')),
            array('en_US_POSIX', new \TYPO3\Flow\I18n\Locale('en')),
            array('en_Shaw', new \TYPO3\Flow\I18n\Locale('en')),
        );
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
