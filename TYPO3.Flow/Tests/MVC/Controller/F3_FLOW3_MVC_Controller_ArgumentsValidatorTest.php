<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/*
 * Testcase for the arguments validator
 *
 * @package
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ArgumentsValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatePropertyTakesTheDatatypeValidatorOfTheArgumentObjectIntoAccount() {
		$errors = $this->getMock('F3\FLOW3\Validation\Errors', array(), array(), '', FALSE);
		$datatypeValidator = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$datatypeValidator->expects($this->once())->method('isValidProperty')->will($this->returnValue(FALSE));

		$customValidator = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$customValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->any())->method('getValidator')->will($this->returnValue($customValidator));
		$argument->expects($this->atLeastOnce())->method('getDatatypeValidator')->will($this->returnValue($datatypeValidator));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory);
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
		$errors = $this->getMock('F3\FLOW3\Validation\Errors', array(), array(), '', FALSE);
		$datatypeValidator = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$datatypeValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$customValidator = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$customValidator->expects($this->once())->method('isValidProperty')->will($this->returnValue(FALSE));

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->atLeastOnce())->method('getValidator')->will($this->returnValue($customValidator));
		$argument->expects($this->any())->method('getDatatypeValidator')->will($this->returnValue($datatypeValidator));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory);
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
		$validatorAddingAnError = new \F3\TestPackage\ValidatorThatAddsAnError($this->objectFactory);
		$mockValidator = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$mockValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$errors = new \F3\FLOW3\Validation\Errors();

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->atLeastOnce())->method('getValidator')->will($this->returnValue($mockValidator));
		$argument->expects($this->any())->method('getDatatypeValidator')->will($this->returnValue($validatorAddingAnError));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory);
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
		$validatorAddingAnError = new \F3\TestPackage\ValidatorThatAddsAnError($this->objectFactory);
		$mockValidator = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$mockValidator->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$errors = new \F3\FLOW3\Validation\Errors();

		$argument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('someArgument'));
		$argument->expects($this->atLeastOnce())->method('getValidator')->will($this->returnValue($validatorAddingAnError));
		$argument->expects($this->any())->method('getDatatypeValidator')->will($this->returnValue($mockValidator));
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->objectFactory);
		$arguments->addArgument($argument);

		$argumentsValidator = new ArgumentsValidator($arguments, $this->objectFactory);
		$argumentsValidator->validateProperty($arguments, 'someArgument', $errors);

		$expectedError = new \F3\FLOW3\Validation\Errors();
		$expectedError[] = new \F3\FLOW3\Validation\Error('The given subject was not valid.', 1221664636);
		$this->assertEquals($expectedError, $errors['someArgument'], 'The error was not stored for the given argument.');
	}
}

?>