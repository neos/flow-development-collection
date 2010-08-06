<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * Testcase for the email address validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class EmailAddressValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\EmailAddressValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid('foo@bar.de');
		$this->assertSame(array(), $validator->getErrors());
	}

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
			array('just+spam@192.168.249.100'),
			array('local@192.168.0.2')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider validAddresses
	 */
	public function emailAddressValidatorReturnsTrueForAValidEmailAddress($address) {
		$emailAddressValidator = new \F3\FLOW3\Validation\Validator\EmailAddressValidator();
		$this->assertTrue($emailAddressValidator->isValid($address));
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
			array('local@192.168.270.1'),
			array('foo@bar.com' . chr(0)),
			array('foo@bar.org' . chr(10)),
			array('andreas@foerthner@example.com'),
			array('some@one.net ')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider invalidAddresses
	 */
	public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress($address) {
		$emailAddressValidator = $this->getMock('F3\FLOW3\Validation\Validator\EmailAddressValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($emailAddressValidator->isValid($address));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress() {
		$emailAddressValidator = $this->getMock('F3\FLOW3\Validation\Validator\EmailAddressValidator', array('addError'), array(), '', FALSE);
		$emailAddressValidator->expects($this->once())->method('addError');
		$emailAddressValidator->isValid('notAValidMail@Address');
	}

}

?>