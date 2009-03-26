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
 * @version $Id: TextValidatorTest.php 1990 2009-03-12 13:59:17Z robert $
 */

/**
 * Testcase for the string length validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: TextValidatorTest.php 1990 2009-03-12 13:59:17Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StringLengthValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stgringLengthValidatorReturnsTrueForAStringShorterThanMaxLengthAndLongerThanMinLength() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('this is a very simple string', $validationErrors, array('minLength' => 0, 'maxLength' => 50)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsFalseForAStringShorterThanThanMinLength() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$stringLengthValidator->injectObjectFactory($mockObjectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertFalse($stringLengthValidator->isValid('this is a very short string', $validationErrors, array('minLength' => 50, 'maxLength' => 100)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsFalseForAStringLongerThanThanMaxLength() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$stringLengthValidator->injectObjectFactory($mockObjectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertFalse($stringLengthValidator->isValid('this is a very short string', $validationErrors, array('minLength' => 5, 'maxLength' => 10)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('this is a very short string', $validationErrors, array('minLength' => 5)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('this is a very short string', $validationErrors, array('maxLength' => 100)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('1234567890', $validationErrors, array('maxLength' => 10)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('1234567890', $validationErrors, array('minLength' => 10)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('1234567890', $validationErrors, array('minLength' => 10, 'maxLength' => 10)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMaxLength() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('1234567890', $validationErrors, array('minLength' => 1, 'maxLength' => 10)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMinLength() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($stringLengthValidator->isValid('1234567890', $validationErrors, array('minLength' => 10, 'maxLength' => 100)));
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Validation\Exception\InvalidValidationOptions
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$stringLengthValidator->isValid('1234567890', $validationErrors, array('minLength' => 101, 'maxLength' => 100));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create');//->with('F3\FLOW3\Validation\Error', 'The length of the given string was not between minLength and maxLength.', 1238108067);

		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$stringLengthValidator->injectObjectFactory($mockObjectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$stringLengthValidator->isValid('this is a very short string', $validationErrors, array('minLength' => 50, 'maxLength' => 100));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$className = uniqid('TestClass');

		eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');

		$object = new $className();

		$this->assertTrue($stringLengthValidator->isValid($object, $validationErrors, array('minLength' => 5, 'maxLength' => 100)));
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Validation\Exception\InvalidSubject
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorThrowsAnExceptionIfTheGivenObjectCanNotBeConvertedToAString() {
		$stringLengthValidator = new \F3\FLOW3\Validation\Validator\StringLengthValidator();
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$className = uniqid('TestClass');

		eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');

		$object = new $className();

		$stringLengthValidator->isValid($object, $validationErrors, array('minLength' => 5, 'maxLength' => 100));
	}
}

?>