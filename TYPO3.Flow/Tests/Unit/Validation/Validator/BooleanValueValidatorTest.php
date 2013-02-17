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

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the true validator
 *
 */
class BooleanValueValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\BooleanValueValidator';

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString() {
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsNull() {
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsTrueAndNoOptionIsSet() {
		$this->assertFalse($this->validator->validate(TRUE)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsFalseAndExpectedValueIsFalse() {
		$this->validatorOptions(array('expectedValue' => FALSE));
		$this->assertFalse($this->validator->validate(FALSE)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorIfTheGivenValueIsAString() {
		$this->assertTrue($this->validator->validate('1')->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorIfTheGivenValueIsFalse() {
		$this->assertTrue($this->validator->validate(FALSE)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorIfTheGivenValueIsAnInteger() {
		$this->assertTrue($this->validator->validate(1)->hasErrors());
	}

}

?>