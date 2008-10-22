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
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function emailAddressValidatorReturnsTrueForACorrectEmailAddress1() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($emailAddressValidator->isValidProperty('andreas.foerthner@netlogix.de', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function emailAddressValidatorReturnsFalseForAnEmailAddressWithIncompleteHostPart() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($emailAddressValidator->isValidProperty('andreas.foerthner@netlogix', $validationErrors));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsFalseForAnEmailAddressWithMissingHostPart() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($emailAddressValidator->isValidProperty('andreas.foerthner@', $validationErrors));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsFalseForAnEmailAddressWithMissingLocalPart() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($emailAddressValidator->isValidProperty('@typo3.org', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function emailValidatorCreatesTheCorrectErrorObjectForAnInvalidEmailAddress() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$emailAddressValidator->isValidProperty('notAValidMail@Address', $validationErrors);

		$this->assertType('F3::FLOW3::Validation::Error', $validationErrors[0]);
		$this->assertEquals(1221559976, $validationErrors[0]->getErrorCode());
	}


	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsTrueForACorrectEmailAddress2() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($emailAddressValidator->isValidProperty('user@localhost', $validationErrors), 'user@localhost was rejected.');
	}


	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsTrueForACorrectEmailAddress3() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($emailAddressValidator->isValidProperty('user@localhost.localdomain', $validationErrors), 'user@localhost.localdomain was rejected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsTrueForACorrectEmailAddress4() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($emailAddressValidator->isValidProperty('info@guggenheim.museum', $validationErrors), 'info@guggenheim.museum was rejected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsTrueForACorrectEmailAddress5() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($emailAddressValidator->isValidProperty('just@test.invalid', $validationErrors), 'just@test.invalid was rejected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsTrueForACorrectEmailAddressUsingAnIPAddressForHost() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($emailAddressValidator->isValidProperty('local@192.168.0.2', $validationErrors), 'local@192.168.0.2 was rejected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsFalseForAnEmailAddressUsingAnIncorrectIPAddressForHost1() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($emailAddressValidator->isValidProperty('local@192.168.2', $validationErrors), 'local@192.168.2 was accepted.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function emailAddressValidatorReturnsFalseForAnEmailAddressUsingAnIncorrectIPAddressForHost2() {
		$emailAddressValidator = new F3::FLOW3::Validation::Validator::EmailAddress();
		$emailAddressValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($emailAddressValidator->isValidProperty('local@192.168.270.1', $validationErrors), 'local@192.168.270.1 was accepted.');
	}

}

?>