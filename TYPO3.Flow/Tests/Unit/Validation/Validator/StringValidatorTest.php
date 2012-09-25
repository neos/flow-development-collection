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
 * Testcase for the string length validator
 *
 */
class StringValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\StringValidator';

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
	public function stringValidatorShouldValidateString() {
		$this->assertFalse($this->validator->validate('Hello World')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringValidatorShouldReturnErrorIfNumberIsGiven() {
		$this->assertTrue($this->validator->validate(42)->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));

		eval('
			class ' . $className . ' {
				public function __toString() {
					return "ASDF";
				}
			}
		');
		$object = new $className();
		$this->assertTrue($this->validator->validate($object)->hasErrors());
	}

}

?>