<?php
namespace TYPO3\FLOW3\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the string length validator
 *
 */
class StringLengthValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\StringLengthValidator';

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsNull() {
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString() {
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 50));
		$this->assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength() {
		$this->validatorOptions(array('minimum' => 50, 'maximum' => 100));
		$this->assertTrue($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength() {
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 10));
		$this->assertTrue($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified() {
		$this->validatorOptions(array('minimum' => 5));
		$this->assertFalse($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified() {
		$this->validatorOptions(array('maximum' => 100));
		$this->assertFalse($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified() {
		$this->validatorOptions(array('maximum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified() {
		$this->validatorOptions(array('minimum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue() {
		$this->validatorOptions(array('minimum' => 10, 'maximum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsfTheStringLengthIsEqualToMaxLength() {
		$this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength() {
		$this->validatorOptions(array('minimum' => 10, 'maximum' => 100));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Validation\Exception\InvalidValidationOptionsException
	 */
	public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength() {
		$this->validator = $this->getMock('TYPO3\FLOW3\Validation\Validator\StringLengthValidator', array('addError'), array(), '', FALSE);
		$this->validatorOptions(array('minimum' => 101, 'maximum' => 100));
		$this->validator->validate('1234567890');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails() {
		$this->validatorOptions(array('minimum' => 50, 'maximum' => 100));

		$this->assertEquals(1, count($this->validator->validate('this is a very short string')->getErrors()));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod() {
		$this->validator = $this->getMock('TYPO3\FLOW3\Validation\Validator\StringLengthValidator', array('addError'), array(), '', FALSE);
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 100));

		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));

		eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');

		$object = new $className();
		$this->assertFalse($this->validator->validate($object)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString() {
		$this->validator = $this->getMock('TYPO3\FLOW3\Validation\Validator\StringLengthValidator', array('addError'), array(), '', FALSE);
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 100));

		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));

		eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');

		$object = new $className();
		$this->assertTrue($this->validator->validate($object)->hasErrors());
	}
}

?>