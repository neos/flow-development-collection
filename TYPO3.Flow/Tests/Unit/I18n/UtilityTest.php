<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 */

/**
 * Testcase for the Locale Utility
 *
 */
class UtilityTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Data provider with valid Accept-Language headers and expected results.
	 *
	 * @return array
	 */
	public function sampleHttpAcceptLanguageHeaders() {
		return array(
			array('pl, en-gb;q=0.8, en;q=0.7', array('pl', 'en-gb', 'en')),
			array('de, *;q=0.8', array('de', '*')),
			array('sv, wont-accept;q=0.8, en;q=0.5', array('sv', 'en')),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleHttpAcceptLanguageHeaders
	 */
	public function httpAcceptLanguageHeadersAreParsedCorrectly($acceptLanguageHeader, array $expectedResult) {
		$languages = \TYPO3\Flow\I18n\Utility::parseAcceptLanguageHeader($acceptLanguageHeader);
		$this->assertEquals($expectedResult, $languages);
	}

	/**
	 * Data provider with filenames with locale tags and expected results.
	 *
	 * @return array
	 */
	public function filenamesWithLocale() {
		return array(
			array('foobar.en_GB.ext', 'en_GB'),
			array('en_GB.xlf', 'en_GB'),
			array('foobar.ext', FALSE),
			array('foobar', FALSE),
			array('foobar.php.tmpl', FALSE),
			array('foobar.rss.php', FALSE),
			array('foobar.xml.php', FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider filenamesWithLocale
	 */
	public function localeIdentifiersAreCorrectlyExtractedFromFilename($filename, $expectedResult) {
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
	public function sampleHaystackStringsAndNeedleStrings() {
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
	public function stringIsFoundAtBeginningOfAnotherString($haystack, $needle, $comparison) {
		$expectedResult = ($comparison === 'beginning' || $comparison === 'both') ? TRUE : FALSE;
		$result = \TYPO3\Flow\I18n\Utility::stringBeginsWith($haystack, $needle);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleHaystackStringsAndNeedleStrings
	 */
	public function stringIsFoundAtEndingOfAnotherString($haystack, $needle, $comparison) {
		$expectedResult = ($comparison === 'ending' || $comparison === 'both') ? TRUE : FALSE;
		$result = \TYPO3\Flow\I18n\Utility::stringEndsWith($haystack, $needle);
		$this->assertEquals($expectedResult, $result);
	}
}
