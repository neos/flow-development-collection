<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

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
 * @version $Id:F3_FLOW3_Object_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class LocaleTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers() {
		try {
			new \F3\FLOW3\Locale\Locale('');
			$this->fail('Empty string');
		} catch(\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifier $exception) {
		}

		try {
			new \F3\FLOW3\Locale\Locale('E');
			$this->fail('Single letter');
		} catch(\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifier $exception) {
		}

		try {
			new \F3\FLOW3\Locale\Locale('deDE');
			$this->fail('No underscore');
		} catch(\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifier $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorRecognizesTheMostImportantValidLocaleIdentifiers() {
		$locale = new \F3\FLOW3\Locale\Locale('de');
		$this->assertEquals('de', $locale->getLanguage());

		$locale = new \F3\FLOW3\Locale\Locale('de_DE');
		$this->assertEquals('de', $locale->getLanguage());
		$this->assertEquals('DE', $locale->getRegion());

		$locale = new \F3\FLOW3\Locale\Locale('en_Latn_US');
		$this->assertEquals('en', $locale->getLanguage());
		$this->assertEquals('Latn', $locale->getScript());
		$this->assertEquals('US', $locale->getRegion());

		$locale = new \F3\FLOW3\Locale\Locale('AR-arab_ae');
		$this->assertEquals('ar', $locale->getLanguage());
		$this->assertEquals('Arab', $locale->getScript());
		$this->assertEquals('AE', $locale->getRegion());
	}
}
?>