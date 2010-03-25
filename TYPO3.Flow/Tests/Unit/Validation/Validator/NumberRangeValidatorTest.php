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
 * Testcase for the number range validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberRangeValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsTrueForASimpleIntegerInRange() {
		$numberRangeValidator = new \F3\FLOW3\Validation\Validator\NumberRangeValidator();
		$numberRangeValidator->setOptions(array('minimum' => 0, 'maximum' => 1000));

		$this->assertTrue($numberRangeValidator->isValid(10.5));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsFalseForANumberOutOfRange() {
		$numberRangeValidator = $this->getMock('F3\FLOW3\Validation\Validator\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('minimum' => 0, 'maximum' => 1000));
		$this->assertFalse($numberRangeValidator->isValid(1000.1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsTrueForANumberInReversedRange() {
		$numberRangeValidator = $this->getMock('F3\FLOW3\Validation\Validator\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('minimum' => 1000, 'maximum' => 0));
		$this->assertTrue($numberRangeValidator->isValid(100));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsFalseForAString() {
		$numberRangeValidator = $this->getMock('F3\FLOW3\Validation\Validator\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('minimum' => 0, 'maximum' => 1000));
		$this->assertFalse($numberRangeValidator->isValid('not a number'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForANumberOutOfRange() {
		$numberRangeValidator = $this->getMock('F3\FLOW3\Validation\Validator\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError');
		$numberRangeValidator->setOptions(array('minimum' => 1, 'maximum' => 42));
		$numberRangeValidator->isValid(4711);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForAStringSubject() {
		$numberRangeValidator = $this->getMock('F3\FLOW3\Validation\Validator\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError');
		$numberRangeValidator->setOptions(array('minimum' => 0, 'maximum' => 42));
		$numberRangeValidator->isValid('this is not between 0 an 42');
	}
}

?>