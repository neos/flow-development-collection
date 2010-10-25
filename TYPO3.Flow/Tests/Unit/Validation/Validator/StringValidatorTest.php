<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Validation\Validator;

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
 * Testcase for the string length validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StringValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\StringValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid('foo');
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function stringValidatorReturnsTrueIfStringIsGiven() {
		$stringValidator = $this->getMock('F3\FLOW3\Validation\Validator\StringValidator', array('addError'), array(), '', FALSE);
		$this->assertTrue($stringValidator->isValid("Hello World"));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function stringValidatorReturnsFalseIfNumberIsGiven() {
		$stringValidator = $this->getMock('F3\FLOW3\Validation\Validator\StringValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($stringValidator->isValid(42));
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function stringValidatorReturnsFalseIfObjectWithToStringMethodStringIsGiven() {
		$stringValidator = $this->getMock('F3\FLOW3\Validation\Validator\StringValidator', array('addError'), array(), '', FALSE);
		$className = uniqid('TestClass');

		eval('
			class ' . $className . ' {
				public function __toString() {
					return "ASDF";
				}
			}
		');
		$object = new $className();
		$this->assertFalse($stringValidator->isValid($object));
	}

}

?>