<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 *                                                                        */

/**
 * Testcase for the Locale class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class LocaleTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers() {
		try {
			new \F3\FLOW3\I18n\Locale('');
			$this->fail('Empty string');
		} catch(\F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
		}

		try {
			new \F3\FLOW3\I18n\Locale('E');
			$this->fail('Single letter');
		} catch(\F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
		}

		try {
			new \F3\FLOW3\I18n\Locale('deDE');
			$this->fail('No underscore');
		} catch(\F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorRecognizesTheMostImportantValidLocaleIdentifiers() {
		$locale = new \F3\FLOW3\I18n\Locale('de');
		$this->assertEquals('de', $locale->getLanguage());
		$this->assertNull($locale->getScript());
		$this->assertNull($locale->getRegion());
		$this->assertNull($locale->getVariant());

		$locale = new \F3\FLOW3\I18n\Locale('de_DE');
		$this->assertEquals('de', $locale->getLanguage());
		$this->assertEquals('DE', $locale->getRegion());
		$this->assertNull($locale->getScript());
		$this->assertNull($locale->getVariant());

		$locale = new \F3\FLOW3\I18n\Locale('en_Latn_US');
		$this->assertEquals('en', $locale->getLanguage());
		$this->assertEquals('Latn', $locale->getScript());
		$this->assertEquals('US', $locale->getRegion());
		$this->assertNull($locale->getVariant());

		$locale = new \F3\FLOW3\I18n\Locale('AR-arab_ae');
		$this->assertEquals('ar', $locale->getLanguage());
		$this->assertEquals('Arab', $locale->getScript());
		$this->assertEquals('AE', $locale->getRegion());
		$this->assertNull($locale->getVariant());
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function producesCorrectLocaleIdentifierWhenStringCasted() {
		$locale = new \F3\FLOW3\I18n\Locale('de_DE');
		$this->assertEquals('de_DE', (string)$locale);

		$locale = new \F3\FLOW3\I18n\Locale('en_Latn_US');
		$this->assertEquals('en_Latn_US', (string)$locale);

		$locale = new \F3\FLOW3\I18n\Locale('AR-arab_ae');
		$this->assertEquals('ar_Arab_AE', (string)$locale);
	}
}

?>