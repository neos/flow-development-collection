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
 * Testcase for the text validator
 *
 */
class TextValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\TextValidator';

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsNull() {
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * Data provider with valid input for TextValidator.
	 * @return array
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString() {
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 */
	public function textValidatorReturnsNoErrorForASimpleString() {
		$this->assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
	}

	public function validInput() {
		return array(
			array('this is a very simple string'),
			array('Ierd Frot uechter mä get, Kirmesdag Milliounen all en, sinn main Stréi mä och. ' . chr(10) . 'Vu dan durch jéngt gréng, ze rou Monn voll stolz. \nKe kille Minutt d\'Kirmes net. Hir Wand Lann Gaas da, wär hu Heck Gart zënter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dénen d\'Gaassen eng, eng am virun geplot d\'Lëtzebuerger, get botze rëscht Blieder si. Dat Dauschen schéinste Milliounen fu. Ze riede méngem Keppchen déi, si gét fergiess erwaacht, räich jéngt duerch en nun. Gëtt Gaas d\'Vullen hie hu, laacht Grénge der dé. Gemaacht gehéiert da aus, gutt gudden d\'wäiss mat wa.'),
			array('3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.'),
		);
		$textValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($textValidator->isValid($input));
	}

	/**
	 * @test
	 * @dataProvider validInput
	 * @param string $input
	 */
	public function textValidatorAcceptsValidInput($input) {
		$textValidator = new \TYPO3\FLOW3\Validation\Validator\TextValidator();
		$this->assertFalse($textValidator->validate($input)->hasErrors());
	}

	/**
	 * Data provider with invalid input for TextValidator.
	 * @return array
	 */
	public function invalidInput() {
		return array(
			array('<span style="color: #BBBBBB;">a nice text</span>')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidInput
	 * @param string $input
	 */
	public function textValidatorRejectsInvalidInput($input) {
		$this->assertTrue($this->validator->validate($input)->hasErrors());
	}

	/**
	 * @test
	 */
	public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities() {
		$expected = array(new \TYPO3\FLOW3\Validation\Error('Valid text without any XML tags is expected.', 1221565786));
		$this->assertEquals($expected, $this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->getErrors());
	}
}

?>