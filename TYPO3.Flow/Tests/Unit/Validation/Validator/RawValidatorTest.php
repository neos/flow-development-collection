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
 * Testcase for the raw validator
 *
 */
class RawValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\RawValidator';

	/**
	 * @test
	 */
	public function theRawValidatorAlwaysReturnsNoErrors() {
		$rawValidator = new \TYPO3\Flow\Validation\Validator\RawValidator(array());

		$this->assertFalse($rawValidator->validate('simple1expression')->hasErrors());
		$this->assertFalse($rawValidator->validate('')->hasErrors());
		$this->assertFalse($rawValidator->validate(NULL)->hasErrors());
		$this->assertFalse($rawValidator->validate(FALSE)->hasErrors());
		$this->assertFalse($rawValidator->validate(new \ArrayObject())->hasErrors());
	}
}
