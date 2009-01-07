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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class EmailAddressTest extends \F3\Testing\BaseTestCase {

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
		$emailAddressValidator = new \F3\FLOW3\Validation\Validator\EmailAddress();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

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
		$error = new \F3\FLOW3\Validation\Error('', 1221559976);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->will($this->returnValue($error));

		$emailAddressValidator = new \F3\FLOW3\Validation\Validator\EmailAddress();
		$emailAddressValidator->injectObjectFactory($mockObjectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertFalse($emailAddressValidator->isValidProperty($address, $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function emailValidatorCreatesTheCorrectErrorObjectForAnInvalidEmailAddress() {
		$error = new \F3\FLOW3\Validation\Error('', 1221559976);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->will($this->returnValue($error));

		$emailAddressValidator = new \F3\FLOW3\Validation\Validator\EmailAddress();
		$emailAddressValidator->injectObjectFactory($mockObjectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$emailAddressValidator->isValidProperty('notAValidMail@Address', $validationErrors);

		$this->assertType('F3\FLOW3\Validation\Error', $validationErrors[0]);
		$this->assertEquals(1221559976, $validationErrors[0]->getErrorCode());
	}

}

?>