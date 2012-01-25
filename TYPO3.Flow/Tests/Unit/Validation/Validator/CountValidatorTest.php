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
 * Testcase for the count validator
 *
 */
class CountValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\CountValidator';

	/**
	 * @test
	 */
	public function countValidatorReturnsNoErrorsIfTheGivenValueIsNull() {
		$this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function countValidatorReturnsNoErrorsIfTheGivenStringIsEmpty() {
		$this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @return array
	 */
	public function countables() {
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage->attach(new \stdClass);
		return array(
			array(array('Foo', 'Bar')),
			array(new \ArrayObject(array('Baz', 'Quux'))),
			array($splObjectStorage)
		);
	}

	/**
	 * @test
	 * @dataProvider countables
	 */
	public function countValidatorReturnsNoErrorsForValidCountables($countable) {
		$this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertFalse($this->validator->validate($countable)->hasErrors());
	}

	/**
	 * @test
	 * @dataProvider countables
	 */
	public function countValidatorReturnsErrorsForInvalidCountables($countable) {
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 10));
		$this->assertTrue($this->validator->validate($countable)->hasErrors());
	}

	/**
	 */
	public function nonCountables() {
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage->attach(new \stdClass);
		return array(
			array('Bar'),
			array(1),
			array(new \stdClass)
		);
	}

	/**
	 * @test
	 * @dataProvider nonCountables
	 */
	public function countValidatorReturnsErrorsForNonCountables($nonCountable) {
		$this->assertTrue($this->validator->validate($nonCountable)->hasErrors());
	}
}
?>