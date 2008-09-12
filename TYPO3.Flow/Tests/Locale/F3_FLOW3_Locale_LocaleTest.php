<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Locale;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Locale
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the Locale class
 *
 * @package FLOW3
 * @subpackage Locale
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class LocaleTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aLocaleIsPrototype() {
		$locale1 = $this->componentFactory->getComponent('F3::FLOW3::Locale::Locale', 'de_DE');
		$locale2 = $this->componentFactory->getComponent('F3::FLOW3::Locale::Locale', 'de_DE');

		$this->assertNotSame($locale1, $locale2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers() {
		try {
			new F3::FLOW3::Locale::Locale('');
			$this->fail('Empty string');
		} catch(F3::FLOW3::Locale::Exception::InvalidLocaleIdentifier $exception) {
		}

		try {
			new F3::FLOW3::Locale::Locale('E');
			$this->fail('Single letter');
		} catch(F3::FLOW3::Locale::Exception::InvalidLocaleIdentifier $exception) {
		}

		try {
			new F3::FLOW3::Locale::Locale('deDE');
			$this->fail('No underscore');
		} catch(F3::FLOW3::Locale::Exception::InvalidLocaleIdentifier $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorRecognizesTheMostImportantValidLocaleIdentifiers() {
		$locale = new F3::FLOW3::Locale::Locale('de');
		$this->assertEquals('de', $locale->getLanguage());

		$locale = new F3::FLOW3::Locale::Locale('de_DE');
		$this->assertEquals('de', $locale->getLanguage());
		$this->assertEquals('DE', $locale->getRegion());

		$locale = new F3::FLOW3::Locale::Locale('en_Latn_US');
		$this->assertEquals('en', $locale->getLanguage());
		$this->assertEquals('Latn', $locale->getScript());
		$this->assertEquals('US', $locale->getRegion());

		$locale = new F3::FLOW3::Locale::Locale('AR-arab_ae');
		$this->assertEquals('ar', $locale->getLanguage());
		$this->assertEquals('Arab', $locale->getScript());
		$this->assertEquals('AE', $locale->getRegion());
	}
}
?>