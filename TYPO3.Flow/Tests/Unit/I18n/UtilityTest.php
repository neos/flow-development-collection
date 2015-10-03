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
 * Testcase for the Locale Utility
 *
 */
class UtilityTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Data provider with valid Accept-Language headers and expected results.
     *
     * @return array
     */
    public function sampleHttpAcceptLanguageHeaders()
    {
        return array(
            array('pl, en-gb;q=0.8, en;q=0.7', array('pl', 'en-gb', 'en')),
            array('de, *;q=0.8', array('de', '*')),
            array('sv, wont-accept;q=0.8, en;q=0.5', array('sv', 'en')),
            array('de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4', array('de-DE', 'de', 'en-US', 'en')),
        );
    }

    /**
     * @test
     * @dataProvider sampleHttpAcceptLanguageHeaders
     */
    public function httpAcceptLanguageHeadersAreParsedCorrectly($acceptLanguageHeader, array $expectedResult)
    {
        $languages = \TYPO3\Flow\I18n\Utility::parseAcceptLanguageHeader($acceptLanguageHeader);
        $this->assertEquals($expectedResult, $languages);
    }

    /**
     * Data provider with filenames with locale tags and expected results.
     *
     * @return array
     */
    public function filenamesWithLocale()
    {
        return array(
            array('foobar.en_GB.ext', 'en_GB'),
            array('en_GB.xlf', 'en_GB'),
            array('foobar.ext', false),
            array('foobar', false),
            array('foobar.php.tmpl', false),
            array('foobar.rss.php', false),
            array('foobar.xml.php', false),
        );
    }

    /**
     * @test
     * @dataProvider filenamesWithLocale
     */
    public function localeIdentifiersAreCorrectlyExtractedFromFilename($filename, $expectedResult)
    {
        $result = \TYPO3\Flow\I18n\Utility::extractLocaleTagFromFilename($filename);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with haystack strings and needle strings, used to test
     * comparison methods. The third argument denotes whether needle is same
     * as beginning of the haystack, or it's ending, or both or none.
     *
     * @return array
     */
    public function sampleHaystackStringsAndNeedleStrings()
    {
        return array(
            array('teststring', 'test', 'beginning'),
            array('foo', 'bar', 'none'),
            array('baz', '', 'none'),
            array('foo', 'foo', 'both'),
            array('foobaz', 'baz', 'ending'),
        );
    }

    /**
     * @test
     * @dataProvider sampleHaystackStringsAndNeedleStrings
     */
    public function stringIsFoundAtBeginningOfAnotherString($haystack, $needle, $comparison)
    {
        $expectedResult = ($comparison === 'beginning' || $comparison === 'both') ? true : false;
        $result = \TYPO3\Flow\I18n\Utility::stringBeginsWith($haystack, $needle);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     * @dataProvider sampleHaystackStringsAndNeedleStrings
     */
    public function stringIsFoundAtEndingOfAnotherString($haystack, $needle, $comparison)
    {
        $expectedResult = ($comparison === 'ending' || $comparison === 'both') ? true : false;
        $result = \TYPO3\Flow\I18n\Utility::stringEndsWith($haystack, $needle);
        $this->assertEquals($expectedResult, $result);
    }
}
