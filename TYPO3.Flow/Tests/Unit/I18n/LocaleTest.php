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
 * Testcase for the Locale class
 *
 */
class LocaleTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers() {
		try {
			new \TYPO3\FLOW3\I18n\Locale('');
			$this->fail('Empty string');
		} catch(\TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
		}

		try {
			new \TYPO3\FLOW3\I18n\Locale('E');
			$this->fail('Single letter');
		} catch(\TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
		}

		try {
			new \TYPO3\FLOW3\I18n\Locale('deDE');
			$this->fail('No underscore');
		} catch(\TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
		}
	}

	/**
	 * @test
	 */
	public function theConstructorRecognizesTheMostImportantValidLocaleIdentifiers() {
		$locale = new \TYPO3\FLOW3\I18n\Locale('de');
		$this->assertEquals('de', $locale->getLanguage());
		$this->assertNull($locale->getScript());
		$this->assertNull($locale->getRegion());
		$this->assertNull($locale->getVariant());

		$locale = new \TYPO3\FLOW3\I18n\Locale('de_DE');
		$this->assertEquals('de', $locale->getLanguage());
		$this->assertEquals('DE', $locale->getRegion());
		$this->assertNull($locale->getScript());
		$this->assertNull($locale->getVariant());

		$locale = new \TYPO3\FLOW3\I18n\Locale('en_Latn_US');
		$this->assertEquals('en', $locale->getLanguage());
		$this->assertEquals('Latn', $locale->getScript());
		$this->assertEquals('US', $locale->getRegion());
		$this->assertNull($locale->getVariant());

		$locale = new \TYPO3\FLOW3\I18n\Locale('AR-arab_ae');
		$this->assertEquals('ar', $locale->getLanguage());
		$this->assertEquals('Arab', $locale->getScript());
		$this->assertEquals('AE', $locale->getRegion());
		$this->assertNull($locale->getVariant());
	}

	/**
	 * @test
	 */
	public function producesCorrectLocaleIdentifierWhenStringCasted() {
		$locale = new \TYPO3\FLOW3\I18n\Locale('de_DE');
		$this->assertEquals('de_DE', (string)$locale);

		$locale = new \TYPO3\FLOW3\I18n\Locale('en_Latn_US');
		$this->assertEquals('en_Latn_US', (string)$locale);

		$locale = new \TYPO3\FLOW3\I18n\Locale('AR-arab_ae');
		$this->assertEquals('ar_Arab_AE', (string)$locale);
	}
}

?>