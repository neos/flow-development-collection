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
 * Testcase for the Controller Arguments Validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ArgumentsValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidReturnsFALSEIfAtLeastOneArgumentIsInvalid() {
		$mockArgument1 = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('foo'));

		$mockArgument2 = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('bar'));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));
		$arguments->addArgument($mockArgument1);
		$arguments->addArgument($mockArgument2);

		$validator = $this->getMock('F3\FLOW3\MVC\Controller\ArgumentsValidator', array('isPropertyValid'), array(), '', FALSE);
		$validator->expects($this->at(0))->method('isPropertyValid')->with($arguments, 'foo')->will($this->returnValue(FALSE));

		$this->assertFalse($validator->isValid($arguments));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidReturnsTRUEIfAllArgumentsAreValid() {
		$mockArgument1 = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('foo'));

		$mockArgument2 = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('bar'));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));
		$arguments->addArgument($mockArgument1);
		$arguments->addArgument($mockArgument2);

		$validator = $this->getMock('F3\FLOW3\MVC\Controller\ArgumentsValidator', array('isPropertyValid'), array(), '', FALSE);
		$validator->expects($this->at(0))->method('isPropertyValid')->with($arguments, 'foo')->will($this->returnValue(TRUE));
		$validator->expects($this->at(1))->method('isPropertyValid')->with($arguments, 'bar')->will($this->returnValue(TRUE));

		$this->assertTrue($validator->isValid($arguments));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canValidateIsOnlyTrueForArgumentsObjects() {
		$validator = new \F3\FLOW3\MVC\Controller\ArgumentsValidator();

		$this->assertTrue($validator->canValidate($this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE)));
		$this->assertFalse($validator->canValidate(new \stdClass));
		$this->assertFalse($validator->canValidate('foo'));
		$this->assertFalse($validator->canValidate(42));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPropertyValidOnlyAcceptsArgumentsObjects() {
		$validator = new \F3\FLOW3\MVC\Controller\ArgumentsValidator();
		$validator->isPropertyValid(new \stdClass, 'foo');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPropertyValidChecksValidatorConjunctionDefinedInAnArgument() {
		$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidatorChain->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockArgumentError = $this->getMock('F3\FLOW3\MVC\Controller\ArgumentError', array('addErrors'), array('foo'));
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\MVC\Controller\ArgumentError')->will($this->returnValue($mockArgumentError));

		$mockArgument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$mockArgument->expects($this->any())->method('getValidator')->will($this->returnValue($mockValidatorChain));
		$mockArgument->expects($this->any())->method('getDataType')->will($this->returnValue('FooDataType'));
		$mockArgument->expects($this->any())->method('getValue')->will($this->returnValue('fooValue'));
		$mockArgument->expects($this->any())->method('isRequired')->will($this->returnValue(TRUE));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));
		$arguments->addArgument($mockArgument);

		$validator = new \F3\FLOW3\MVC\Controller\ArgumentsValidator();
		$validator->injectObjectFactory($mockObjectFactory);

		$mockValidatorChain->expects($this->at(0))->method('isValid')->with('fooValue')->will($this->returnValue(TRUE));
		$mockValidatorChain->expects($this->at(1))->method('isValid')->with('fooValue')->will($this->returnValue(FALSE));

		$this->assertTrue($validator->isPropertyValid($arguments, 'foo'));
		$this->assertFalse($validator->isPropertyValid($arguments, 'foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPropertyValidReturnsTrueIfTheArgumentHasTheDefaultValueAndIsNotRequired() {
		$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$mockArgument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$mockArgument->expects($this->any())->method('getValidator')->will($this->returnValue($mockValidatorChain));
		$mockArgument->expects($this->any())->method('getDataType')->will($this->returnValue('FooDataType'));
		$mockArgument->expects($this->any())->method('getDefaultValue')->will($this->returnValue('defaultValue'));
		$mockArgument->expects($this->any())->method('getValue')->will($this->returnValue('defaultValue'));
		$mockArgument->expects($this->any())->method('isRequired')->will($this->returnValue(FALSE));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));
		$arguments->addArgument($mockArgument);

		$validator = new \F3\FLOW3\MVC\Controller\ArgumentsValidator();

		$mockValidatorChain->expects($this->never())->method('isValid');

		$this->assertTrue($validator->isPropertyValid($arguments, 'foo'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function isPropertyValidCallsAddErrorsForArgumentIfConjunctionIsNotValid() {
		$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidatorChain->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$mockValidatorChain->expects($this->once())->method('getErrors')->will($this->returnValue(array('error')));

		$mockArgument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$mockArgument->expects($this->any())->method('getValidator')->will($this->returnValue($mockValidatorChain));
		$mockArgument->expects($this->any())->method('getDataType')->will($this->returnValue('FooDataType'));
		$mockArgument->expects($this->any())->method('getValue')->will($this->returnValue('defaultValue'));
		$mockArgument->expects($this->any())->method('isRequired')->will($this->returnValue(TRUE));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));
		$arguments->addArgument($mockArgument);

		$validator = $this->getMock('F3\FLOW3\MVC\Controller\ArgumentsValidator', array('addErrorsForArgument'));
		$validator->expects($this->once())->method('addErrorsForArgument')->with(array('error'), 'foo');

		$validator->isPropertyValid($arguments, 'foo');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function addErrorsForArgumentAddsErrorsToNewArgumentErrorIndexedByArgumentName() {
		$mockArgumentError = $this->getMock('F3\FLOW3\MVC\Controller\ArgumentError', array('addErrors'), array('foo'));
		$mockArgumentError->expects($this->once())->method('addErrors')->with(array('error'));
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\MVC\Controller\ArgumentError')->will($this->returnValue($mockArgumentError));

		$validator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ArgumentsValidator'), array('dummy'));
		$validator->injectObjectFactory($mockObjectFactory);
		$validator->_call('addErrorsForArgument', array('error'), 'foo');

		$errors = $validator->getErrors();
		$this->assertEquals($mockArgumentError, $errors['foo']);
	}

}
?>