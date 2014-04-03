<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Disjunction Validator
 *
 */
class DisjunctionValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function validateReturnsNoErrorsIfOneValidatorReturnsNoError() {
		$validatorDisjunction = new \TYPO3\Flow\Validation\Validator\DisjunctionValidator(array());
		$validatorObject = $this->getMock('TYPO3\Flow\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new \TYPO3\Flow\Error\Result()));

		$errors = new \TYPO3\Flow\Error\Result();
		$errors->addError(new \TYPO3\Flow\Error\Error('Error', 123));

		$secondValidatorObject = $this->getMock('TYPO3\Flow\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsAllErrorsIfAllValidatorsReturnErrrors() {
		$validatorDisjunction = new \TYPO3\Flow\Validation\Validator\DisjunctionValidator(array());

		$error1 = new \TYPO3\Flow\Error\Error('Error', 123);
		$error2 = new \TYPO3\Flow\Error\Error('Error2', 123);

		$errors1 = new \TYPO3\Flow\Error\Result();
		$errors1->addError($error1);
		$validatorObject = $this->getMock('TYPO3\Flow\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors1));

		$errors2 = new \TYPO3\Flow\Error\Result();
		$errors2->addError($error2);
		$secondValidatorObject = $this->getMock('TYPO3\Flow\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors2));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertEquals(array($error1, $error2), $validatorDisjunction->validate('some subject')->getErrors());
	}
}
