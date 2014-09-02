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
 * Testcase for the regular expression validator
 *
 */
class RegularExpressionValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\RegularExpressionValidator';

	/**
	 * Looks empty - and that's the purpose: do not run the parent's setUp().
	 */
	public function setUp() {}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
	 */
	public function validateThrowsExceptionIfExpressionIsEmpty() {
		$this->validatorOptions(array());
		$this->validator->validate('foo');
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsNull() {
		$this->validatorOptions(array('regularExpression' => '/^.*$/'));
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString() {
		$this->validatorOptions(array('regularExpression' => '/^.*$/'));
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$this->validatorOptions(array('regularExpression' => '/^simple[0-9]expression$/'));

		$this->assertFalse($this->validator->validate('simple1expression')->hasErrors());
		$this->assertTrue($this->validator->validate('simple1expressions')->hasErrors());
	}

	/**
	 * @test
	 */
	public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch() {
		$this->validatorOptions(array('regularExpression' => '/^simple[0-9]expression$/'));
		$subject = 'some subject that will not match';
		$errors = $this->validator->validate($subject)->getErrors();
		$this->assertEquals(array(new \TYPO3\Flow\Validation\Error('The given subject did not match the pattern. Got: %1$s', 1221565130, array($subject))), $errors);
	}
}
