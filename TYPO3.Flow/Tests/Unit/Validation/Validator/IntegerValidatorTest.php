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
 * Testcase for the integer validator
 *
 */
class IntegerValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\IntegerValidator';

	/**
	 * Data provider with valid integers
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validIntegers() {
		return array(
			array(1029437),
			array('12345'),
			array('+12345'),
			array('-12345')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider validIntegers
	 */
	public function integerValidatorReturnsNoErrorsForAValidInteger($integer) {
		$this->assertFalse($this->validator->validate($integer)->hasErrors());
	}

	/**
	 * Data provider with invalid integers
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidIntegers() {
		return array(
			array('not a number'),
			array(3.1415),
			array('12345.987')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider invalidIntegers
	 */
	public function integerValidatorReturnsErrorForAnInvalidInteger($invalidInteger) {
		$this->assertTrue($this->validator->validate($invalidInteger)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$this->assertEquals(1, count($this->validator->validate('not a number')->getErrors()));
	}

}

?>