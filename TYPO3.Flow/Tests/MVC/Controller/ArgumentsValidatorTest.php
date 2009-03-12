<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Testcase for the arguments validator
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ArgumentsValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatePropertyTakesTheDatatypeValidatorOfTheArgumentObjectIntoAccount() {
		$this->markTestIncomplete();

		$errors = $this->getMock('F3\FLOW3\Validation\Errors', array(), array(), '', FALSE);
		$datatypeValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$datatypeValidator->expects($this->once())->method('isValidProperty')->will($this->returnValue(FALSE));

		$customValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$customValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->any())->method('getValidator')->will($this->returnValue($customValidator));
		$argument->expects($this->atLeastOnce())->method('getDatatypeValidator')->will($this->returnValue($datatypeValidator));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory, $this->objectManager);
		$arguments->addArgument($argument);

		$argumentsValidator = new ArgumentsValidator($arguments, $this->objectFactory);
		$this->assertFalse($argumentsValidator->validateProperty($arguments, 'someArgument', $errors));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatePropertyTakesTheRegisteredCustomValidatorOfTheArgumentObjectIntoAccount() {
		$this->markTestIncomplete();

		$errors = $this->getMock('F3\FLOW3\Validation\Errors', array(), array(), '', FALSE);
		$datatypeValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$datatypeValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$customValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$customValidator->expects($this->once())->method('isValidProperty')->will($this->returnValue(FALSE));

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->atLeastOnce())->method('getValidator')->will($this->returnValue($customValidator));
		$argument->expects($this->any())->method('getDatatypeValidator')->will($this->returnValue($datatypeValidator));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory, $this->objectManager);
		$arguments->addArgument($argument);

		$argumentsValidator = new ArgumentsValidator($arguments, $this->objectFactory);
		$this->assertFalse($argumentsValidator->validateProperty($arguments, 'someArgument', $errors));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatePropertyAddsTheErrorObjectsAddedByTheDatatypeValidatorToTheErrorsObject() {
		$this->markTestIncomplete();

		$validatorAddingAnError = new \F3\TestPackage\ValidatorThatAddsAnError($this->objectFactory);
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$errors = new \F3\FLOW3\Validation\Errors();

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->atLeastOnce())->method('getValidator')->will($this->returnValue($mockValidator));
		$argument->expects($this->any())->method('getDatatypeValidator')->will($this->returnValue($validatorAddingAnError));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory, $this->objectManager);
		$arguments->addArgument($argument);

		$argumentsValidator = new ArgumentsValidator($arguments, $this->objectFactory);
		$argumentsValidator->validateProperty($arguments, 'someArgument', $errors);

		$expectedError = new \F3\FLOW3\Validation\Errors();
		$expectedError[] = new \F3\FLOW3\Validation\Error('The given subject was not valid.', 1221664636);
		$this->assertEquals($expectedError, $errors['someArgument'], 'The error was not stored for the given argument.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatePropertyAddsTheErrorObjectsAddedByTheCustomValidatorToTheErrorsObject() {
		$this->markTestIncomplete();

		$validatorAddingAnError = new \F3\TestPackage\ValidatorThatAddsAnError($this->objectFactory);
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$errors = new \F3\FLOW3\Validation\Errors();

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->atLeastOnce())->method('getValidator')->will($this->returnValue($validatorAddingAnError));
		$argument->expects($this->any())->method('getDatatypeValidator')->will($this->returnValue($mockValidator));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory, $this->objectManager);
		$arguments->addArgument($argument);

		$argumentsValidator = new ArgumentsValidator($arguments, $this->objectFactory);
		$argumentsValidator->validateProperty($arguments, 'someArgument', $errors);

		$expectedError = new \F3\FLOW3\Validation\Errors();
		$expectedError[] = new \F3\FLOW3\Validation\Error('The given subject was not valid.', 1221664636);
		$this->assertEquals($expectedError, $errors['someArgument'], 'The error was not stored for the given argument.');
	}
}

?>