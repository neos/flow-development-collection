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
 * Testcase for the Generic Object Validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class GenericObjectValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidReturnsFalseIfTheValueIsNoObject() {
		$validator = $this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array('addError', 'addErrorsForProperty'), array(), '', FALSE);
		$this->assertFalse($validator->isValid('foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidChecksAllPropertiesForWhichAPropertyValidatorExists() {
		$mockPropertyValidators = array('foo' => 'validator', 'bar' => 'validator');
		$mockObject = new \stdClass;

		$validator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\GenericObjectValidator'), array('addError', 'addErrorsForProperty', 'isPropertyValid'), array(), '', FALSE);
		$validator->_set('propertyValidators', $mockPropertyValidators);

		$validator->expects($this->at(0))->method('isPropertyValid')->with($mockObject, 'foo')->will($this->returnValue(TRUE));
		$validator->expects($this->at(1))->method('isPropertyValid')->with($mockObject, 'bar')->will($this->returnValue(TRUE));

		$validator->isValid($mockObject);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function isValidChecksAllPropertiesEvenIfOnePropertyValidatorFailed() {
		$mockPropertyValidators = array('foo' => 'validator', 'bar' => 'validator');
		$mockObject = new \stdClass;

		$validator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\GenericObjectValidator'), array('addError', 'addErrorsForProperty', 'isPropertyValid'), array(), '', FALSE);
		$validator->_set('propertyValidators', $mockPropertyValidators);

		$validator->expects($this->at(0))->method('isPropertyValid')->with($mockObject, 'foo')->will($this->returnValue(FALSE));
		$validator->expects($this->at(1))->method('isPropertyValid')->with($mockObject, 'bar')->will($this->returnValue(TRUE));

		$this->assertEquals(FALSE, $validator->isValid($mockObject));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function isPropertyValidChecksAllValidatorsForAPropertyEvenIfOnePropertyValidatorFailed() {
		$mockValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator1->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$mockValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator2->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$mockPropertyValidators = array('foo' => array($mockValidator1, $mockValidator2));
		$mockObject = new \stdClass;

		$validator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\GenericObjectValidator'), array('addErrorsForProperty'), array(), '', FALSE);
		$validator->_set('propertyValidators', $mockPropertyValidators);

		$this->assertEquals(FALSE, $validator->isPropertyValid($mockObject, 'foo'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function isPropertyAddsErrorsForInvalidProperties() {
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$mockValidator->expects($this->atLeastOnce())->method('getErrors')->will($this->returnValue(array('error')));

		$mockPropertyValidators = array('foo' => array($mockValidator));
		$mockObject = new \stdClass;

		$validator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\GenericObjectValidator'), array('addErrorsForProperty'), array(), '', FALSE);
		$validator->_set('propertyValidators', $mockPropertyValidators);

		$validator->expects($this->once())->method('addErrorsForProperty')->with(array('error'), 'foo');

		$validator->isPropertyValid($mockObject, 'foo');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function addErrorsForPropertyAddsPropertyErrorToErrorsIndexedByPropertyName() {
		$mockPropertyError = $this->getMock('F3\FLOW3\Validation\PropertyError', array('addErrors'), array('foo'));
		$mockPropertyError->expects($this->once())->method('addErrors')->with(array('error'));

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\PropertyError', 'foo')->will($this->returnValue($mockPropertyError));

		$validator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\GenericObjectValidator'), array('dummy'), array(), '', FALSE);
		$validator->_set('objectFactory', $mockObjectFactory);
		$validator->_call('addErrorsForProperty', array('error'), 'foo');

		$errors = $validator->_get('errors');
		$this->assertEquals($mockPropertyError, $errors['foo']);
	}
}

?>