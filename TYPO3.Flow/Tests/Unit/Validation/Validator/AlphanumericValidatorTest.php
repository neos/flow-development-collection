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
 * Testcase for the alphanumeric validator
 *
 */
class AlphanumericValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\AlphanumericValidator';

	/**
	 * @test
	 */
	public function alphanumericValidatorShouldReturnNoErrorsIfTheGivenValueIsNull() {
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorShouldReturnNoErrorsIfTheGivenStringIsEmpty() {
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString() {
		$this->assertFalse($this->validator->validate('12ssDF34daweidf')->hasErrors());
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericStringWithUmlauts() {
		$this->assertFalse($this->validator->validate('12ssDF34daweidfäøüößØLīgaestevimīlojuņščļœøÅ')->hasErrors());
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters() {
		$this->assertTrue($this->validator->validate('adsf%&/$jklsfdö')->hasErrors());
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$this->assertEquals(1, count($this->validator->validate('adsf%&/$jklsfdö')->getErrors()));

	}
}

?>