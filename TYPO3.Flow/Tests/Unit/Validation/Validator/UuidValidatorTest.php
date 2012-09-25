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
 * Testcase for the UUID validator
 *
 */
class UuidValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\UuidValidator';

	/**
	 * @test
	 */
	public function validatorAcceptsCorrectUUIDs() {
		$this->assertFalse($this->validator->validate('e104e469-9030-4b98-babf-3990f07dd3f1')->hasErrors());
		$this->assertFalse($this->validator->validate('533548ca-8914-4a19-9404-ef390a6ce387')->hasErrors());
	}

	/**
	 * @test
	 */
	public function tooShortUUIDIsRejected() {
		$this->assertTrue($this->validator->validate('e104e469-9030-4b98-babf-3990f07')->hasErrors());
	}

	/**
	 * @test
	 */
	public function UUIDWithOtherThanHexValuesIsRejected() {
		$this->assertTrue($this->validator->validate('e104e469-9030-4g98-babf-3990f07dd3f1')->hasErrors());
	}

	/**
	 * @test
	 */
	public function UUIDValidatorCreatesTheCorrectErrorIfTheSubjectIsInvalid() {
		$expected = array(new \TYPO3\Flow\Validation\Error('The given subject was not a valid UUID.', 1221565853));
		$this->assertEquals($expected, $this->validator->validate('e104e469-9030-4b98-babf-3990f07')->getErrors());
	}
}

?>