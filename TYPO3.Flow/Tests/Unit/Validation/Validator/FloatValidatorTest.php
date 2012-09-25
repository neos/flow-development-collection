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
 * Testcase for the float validator
 *
 */
class FloatValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\FloatValidator';

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
	 * Data provider with valid floats
	 *
	 * @return array
	 */
	public function validFloats() {
		return array(
			array(1029437.234726),
			array('123.45'),
			array('+123.45'),
			array('-123.45'),
			array('123.45e3'),
			array(123.45e3)
		);
	}

	/**
	 * @test
	 * @dataProvider validFloats
	 */
	public function floatValidatorReturnsNoErrorsForAValidFloat($float) {
		$this->assertFalse($this->validator->validate($float)->hasErrors());
	}

	/**
	 * Data provider with invalid floats
	 *
	 * @return array
	 */
	public function invalidFloats() {
		return array(
			array(1029437),
			array('1029437'),
			array('foo.bar'),
			array('not a number')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidFloats
	 */
	public function floatValidatorReturnsErrorForAnInvalidFloat($float) {
		$this->assertTrue($this->validator->validate($float)->hasErrors());
	}

	/**
	 * test
	 */
	public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$this->assertEquals(1, count($this->validator->validate(123456)->getErrors()));
	}

}

?>