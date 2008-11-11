<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Validation::Validator;

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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the email address validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class EmailAddressTest extends F3::Testing::BaseTestCase {

	/**
	 * Data provider with valid email addresses
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validAddresses() {
		return array(
			array('andreas.foerthner@netlogix.de'),
			array('user@localhost'),
			array('user@localhost.localdomain'),
			array('info@guggenheim.museum'),
			array('just@test.invalid'),
			array('just+spam@test.de'),
			array('local@192.168.0.2')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider validAddresses
	 */
	public function emailAddressValidatorReturnsTrueForAValidEmailAddress($address) {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($emailAddressValidator->isValidProperty($address, $validationErrors));
	}

	/**
	 * Data provider with invalid email addresses
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidAddresses() {
		return array(
			array('andreas.foerthner@'),
			array('@typo3.org'),
			array('someone@typo3.'),
			array('local@192.168.2'),
			array('local@192.168.270.1')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider invalidAddresses
	 */
	public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress($address) {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($emailAddressValidator->isValidProperty($address, $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function emailValidatorCreatesTheCorrectErrorObjectForAnInvalidEmailAddress() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$emailAddressValidator->isValidProperty('notAValidMail@Address', $validationErrors);

		$this->assertType('F3::FLOW3::Validation::Error', $validationErrors[0]);
		$this->assertEquals(1221559976, $validationErrors[0]->getErrorCode());
	}

}

?>