<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the float validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FloatValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\FloatValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid(1.2);
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * Data provider with valid floats
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider validFloats
	 */
	public function floatValidatorReturnsTrueForAValidFloat($address) {
		$floatValidator = new \F3\FLOW3\Validation\Validator\FloatValidator();
		$this->assertTrue($floatValidator->isValid($address));
	}

	/**
	 * Data provider with invalid floats
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidFloats() {
		return array(
			array(1029437),
			array('1029437'),
			array('not a number')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider invalidFloats
	 */
	public function floatValidatorReturnsFalseForAnInvalidFloat($address) {
		$floatValidator = $this->getMock('F3\FLOW3\Validation\Validator\FloatValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($floatValidator->isValid($address));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$floatValidator = new \F3\FLOW3\Validation\Validator\FloatValidator();
		$floatValidator = $this->getMock('F3\FLOW3\Validation\Validator\FloatValidator', array('addError'), array(), '', FALSE);
		$floatValidator->expects($this->once())->method('addError');
		$floatValidator->isValid(123456);
	}

}

?>