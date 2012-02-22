<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Locale Detector
 *
 */
class DetectorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\Detector
	 */
	protected $detector;

	/**
	 * @return void
	 */
	public function setUp() {
		$findBestMatchingLocaleCallback = function() {
			$args = func_get_args();
			$localeIdentifier = (string)$args[0];

			if (in_array($localeIdentifier, array('en_US_POSIX', 'en_Shaw'))) {
				return new \TYPO3\FLOW3\I18n\Locale('en');
			} else if ($localeIdentifier === 'en_GB') {
				return new \TYPO3\FLOW3\I18n\Locale('en_GB');
			} else if ($localeIdentifier === 'sr_RS') {
				return new \TYPO3\FLOW3\I18n\Locale('sr');
			} else {
				return NULL;
			}
		};

		$mockLocaleCollection = $this->getMock('TYPO3\FLOW3\I18n\LocaleCollection');
		$mockLocaleCollection->expects($this->any())->method('findBestMatchingLocale')->will($this->returnCallback($findBestMatchingLocaleCallback));

		$mockLocalizationService = $this->getMock('TYPO3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->any())->method('getConfiguration')->will($this->returnValue(new \TYPO3\FLOW3\I18n\Configuration('sv_SE')));

		$this->detector = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Detector', array('dummy'));
		$this->detector->_set('localeBasePath', 'vfs://Foo/');
		$this->detector->injectLocaleCollection($mockLocaleCollection);
		$this->detector->injectLocalizationService($mockLocalizationService);
	}

	/**
	 * Data provider with valid Accept-Language headers and expected results.
	 *
	 * @return array
	 */
	public function sampleHttpAcceptLanguageHeaders() {
		return array(
			array('pl, en-gb;q=0.8, en;q=0.7', new \TYPO3\FLOW3\I18n\Locale('en_GB')),
			array('de, *;q=0.8', new \TYPO3\FLOW3\I18n\Locale('sv_SE')),
			array('pl, de;q=0.5, sr-rs;q=0.1', new \TYPO3\FLOW3\I18n\Locale('sr')),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleHttpAcceptLanguageHeaders
	 */
	public function detectingBestMatchingLocaleFromHttpAcceptLanguageHeaderWorksCorrectly($acceptLanguageHeader, $expectedResult) {
		$locale = $this->detector->detectLocaleFromHttpHeader($acceptLanguageHeader);
		$this->assertEquals($expectedResult, $locale);
	}

	/**
	 * Data provider with valid locale identifiers (tags) and expected results.
	 *
	 * @return array
	 */
	public function sampleLocaleIdentifiers() {
		return array(
			array('en_GB', new \TYPO3\FLOW3\I18n\Locale('en_GB')),
			array('en_US_POSIX', new \TYPO3\FLOW3\I18n\Locale('en')),
			array('en_Shaw', new \TYPO3\FLOW3\I18n\Locale('en')),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleLocaleIdentifiers
	 */
	public function detectingBestMatchingLocaleFromLocaleIdentifierWorksCorrectly($localeIdentifier, $expectedResult) {
		$locale = $this->detector->detectLocaleFromLocaleTag($localeIdentifier);
		$this->assertEquals($expectedResult, $locale);
	}
}

?>