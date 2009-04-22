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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the number range validator
 *
 * @package FLOW3
 * @subpackage Tests
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
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));

		$this->assertTrue($numberRangeValidator->isValid(10.5));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsFalseForANumberOutOfRange() {
		$error = new \F3\FLOW3\Validation\Error('', 1221561046);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->will($this->returnValue($error));

		$numberRangeValidator = new \F3\FLOW3\Validation\Validator\NumberRangeValidator();
		$numberRangeValidator->injectObjectFactory($mockObjectFactory);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));

		$this->assertFalse($numberRangeValidator->isValid(1000.1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsTrueForANumberInReversedRange() {
		$numberRangeValidator = new \F3\FLOW3\Validation\Validator\NumberRangeValidator();
		$numberRangeValidator->setOptions(array('startRange' => 1000, 'endRange' => 0));

		$this->assertTrue($numberRangeValidator->isValid(100));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsFalseForAString() {
		$error = new \F3\FLOW3\Validation\Error('', 1221563685);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->will($this->returnValue($error));

		$numberRangeValidator = new \F3\FLOW3\Validation\Validator\NumberRangeValidator();
		$numberRangeValidator->injectObjectFactory($mockObjectFactory);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));

		$this->assertFalse($numberRangeValidator->isValid('not a number'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorObjectForANumberOutOfRange() {
		$error = new \F3\FLOW3\Validation\Error('', 1221561046);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->will($this->returnValue($error));

		$numberRangeValidator = new \F3\FLOW3\Validation\Validator\NumberRangeValidator();
		$numberRangeValidator->injectObjectFactory($mockObjectFactory);
		$numberRangeValidator->setOptions(array('startRange' => 1, 'endRange' => 42));

		$numberRangeValidator->isValid(4711);
		$validationErrors = $numberRangeValidator->getErrors();

		$this->assertType('F3\FLOW3\Validation\Error', $validationErrors[0]);
		$this->assertEquals(1221561046, $validationErrors[0]->getCode());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorObjectForAStringSubject() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\Validation\Error', 'The given subject was not a valid number. Got: "this is not between 1 an 42"', 1221563685);

		$numberRangeValidator = new \F3\FLOW3\Validation\Validator\NumberRangeValidator();
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 42));
		$numberRangeValidator->injectObjectFactory($mockObjectFactory);

		$numberRangeValidator->isValid('this is not between 1 an 42');
	}
}

?>