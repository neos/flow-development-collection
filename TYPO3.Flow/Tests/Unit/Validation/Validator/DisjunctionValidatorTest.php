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
 * Testcase for the Disjunction Validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DisjunctionValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\DisjunctionValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid('foo');
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function allValidatorsInTheDisjunctionAreCalledEvenIfOneReturnsTrue() {
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\ConjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$secondValidatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));
		
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$validatorDisjunction->isValid('some subject');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function isValidReturnsTrueIfOneValidatorReturnsTrue() {
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\DisjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$secondValidatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));
		
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertTrue($validatorDisjunction->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function isValidReturnsFalseIfAllValidatorsReturnFalse() {
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\ConjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$validatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$secondValidatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));
		
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertFalse($validatorDisjunction->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getErrorsIsEmptyForValidDisjunctionEvenIfOneValidatorReturnsFalse() {
		$error1 = new \F3\FLOW3\Validation\Error('foo', 1);
		
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\DisjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$secondValidatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array($error1)));
		
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);
		
		$validatorDisjunction->isValid('some subject');

		$this->assertEquals(0, count($validatorDisjunction->getErrors()));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getErrorsReturnsAllErrorsForInvalidDisjunction() {
		$error1 = new \F3\FLOW3\Validation\Error('foo', 1);
		$error2 = new \F3\FLOW3\Validation\Error('bar', 2);
		
		$validatorDisjunction = new \F3\FLOW3\Validation\Validator\DisjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$validatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array($error1)));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$secondValidatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array($error2)));
		
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);
		
		$validatorDisjunction->isValid('some subject');

		$this->assertEquals(array($error1, $error2), $validatorDisjunction->getErrors());
	}
}

?>