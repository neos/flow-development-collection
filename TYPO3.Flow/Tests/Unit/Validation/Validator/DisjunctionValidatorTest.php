<?php
namespace F3\FLOW3\Tests\Unit\Validation\Validator;

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
 * Testcase for the Disjunction Validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DisjunctionValidatorTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function allValidatorsInTheDisjunctionAreCalledEvenIfOneReturnsNoError() {
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\DisjunctionValidator(array());
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->once())->method('validate')->will($this->returnValue(new \F3\FLOW3\Error\Result()));

		$errors = new \F3\FLOW3\Error\Result();
		$errors->addError(new \F3\FLOW3\Error\Error('Error'));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->exactly(1))->method('validate')->will($this->returnValue($errors));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$validatorDisjunction->validate('some subject');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validateReturnsNoErrorsIfOneValidatorReturnsNoError() {
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\DisjunctionValidator(array());
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new \F3\FLOW3\Error\Result()));

		$errors = new \F3\FLOW3\Error\Result();
		$errors->addError(new \F3\FLOW3\Error\Error('Error'));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validateReturnsAllErrorsIfAllValidatorsReturnErrrors() {
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\DisjunctionValidator(array());

		$error1 = new \F3\FLOW3\Error\Error('Error');
		$error2 = new \F3\FLOW3\Error\Error('Error2');

		$errors1 = new \F3\FLOW3\Error\Result();
		$errors1->addError($error1);
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors1));

		$errors2 = new \F3\FLOW3\Error\Result();
		$errors2->addError($error2);
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors2));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertEquals(array($error1, $error2), $validatorDisjunction->validate('some subject')->getErrors());
	}
}

?>