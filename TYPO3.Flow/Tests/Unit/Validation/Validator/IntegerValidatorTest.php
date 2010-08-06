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
 * Testcase for the integer validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class IntegerValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\IntegerValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid(12);
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * Data provider with valid integers
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validIntegers() {
		return array(
			array(1029437),
			array('12345'),
			array('+12345'),
			array('-12345')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider validIntegers
	 */
	public function integerValidatorReturnsTrueForAValidInteger($integer) {
		$integerValidator = new \F3\FLOW3\Validation\Validator\IntegerValidator();
		$this->assertTrue($integerValidator->isValid($integer));
	}

	/**
	 * Data provider with invalid email addresses
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidIntegers() {
		return array(
			array('not a number'),
			array(3.1415),
			array('12345.987')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider invalidIntegers
	 */
	public function integerValidatorReturnsTrueForAnInvalidInteger($integer) {
		$integerValidator = $this->getMock('F3\FLOW3\Validation\Validator\IntegerValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($integerValidator->isValid($integer));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$integerValidator = $this->getMock('F3\FLOW3\Validation\Validator\IntegerValidator', array('addError'), array(), '', FALSE);
		$integerValidator->expects($this->once())->method('addError');
		$integerValidator->isValid('not a number');
	}

}

?>