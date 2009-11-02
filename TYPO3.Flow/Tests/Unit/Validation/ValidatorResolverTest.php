<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation;

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
 * Testcase for the validator resolver
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ValidatorResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isObjectRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(FALSE));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager));
		$this->assertSame(FALSE, $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegistered() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('isObjectRegistered')->with('Foo')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager));
		$this->assertSame('Foo', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isObjectRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager));
		$this->assertSame('F3\FLOW3\Validation\Validator\FooValidator', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions() {
		$className = uniqid('Test');
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ObjectValidatorInterface', array(), array(), $className);
		$mockValidator->expects($this->once())->method('setOptions')->with(array('foo' => 'bar'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObject')->with($className)->will($this->returnValue($mockValidator));

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver',array('resolveValidatorObjectName'), array($mockObjectManager));
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
		$validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
		$this->assertSame($mockValidator, $validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved() {
		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver',array('resolveValidatorObjectName'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->will($this->returnValue(FALSE));
		$validator = $validatorResolver->createValidator('Foo', array('foo' => 'bar'));
		$this->assertNull($validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseValidatorCachesTheResultOfTheBuildBaseValidatorConjunctionCalls() {
		$mockConjunctionValidator = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('buildBaseValidatorConjunction'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('buildBaseValidatorConjunction')->with('F3\Virtual\Foo')->will($this->returnValue($mockConjunctionValidator));

		$result = $validatorResolver->getBaseValidatorConjunction('F3\Virtual\Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#1');

		$result = $validatorResolver->getBaseValidatorConjunction('F3\Virtual\Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#2');
	}

	/**
	 * dataProvider for parseValidatorAnnotationCanParseAnnotations
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validatorAnnotations() {
		return array(
			array('$var Bar', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Bar', 'validatorOptions' => array())))),
			array('$var Bar, Foo', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Bar', 'validatorOptions' => array()),
						array('validatorName' => 'Foo', 'validatorOptions' => array())
						))),
			array('$var Baz (Foo=Bar)', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => 'Bar'))))),
			array('$var Buzz (Foo="B=a, r", Baz=1)', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Buzz', 'validatorOptions' => array('Foo' => 'B=a, r', 'Baz' => '1'))))),
			array('$var Foo(Baz=1, Bar=Quux)', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Foo', 'validatorOptions' => array('Baz' => '1', 'Bar' => 'Quux'))))),
			array('$var Pax, Foo(Baz = \'1\', Bar = Quux)', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Pax', 'validatorOptions' => array()),
							array('validatorName' => 'Foo', 'validatorOptions' => array('Baz' => '1', 'Bar' => 'Quux'))
						))),
			array('$var Reg (P="[at]*(h|g)"), Quux', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Reg', 'validatorOptions' => array('P' => '[at]*(h|g)')),
							array('validatorName' => 'Quux', 'validatorOptions' => array())
						))),
			array('$var Baz (Foo="B\"ar")', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => 'B"ar'))))),
			array('$var F3\TestPackage\Quux', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'F3\TestPackage\Quux', 'validatorOptions' => array())))),
			array('$var Baz(Foo="5"), Bar(Quux="123")', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => '5')),
							array('validatorName' => 'Bar', 'validatorOptions' => array('Quux' => '123'))))),
			array('$var Baz(Foo="2"), Bar(Quux=123, Pax="a weird \"string\" with *freaky* \\stuff")', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => '2')),
							array('validatorName' => 'Bar', 'validatorOptions' => array('Quux' => '123', 'Pax' => 'a weird "string" with *freaky* \\stuff'))))),
		);
	}

	/**
	 *
	 * @test
	 * @dataProvider validatorAnnotations
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseValidatorAnnotationCanParseAnnotations($annotation, $expectedResult) {
		$validatorResolverClassName = $this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver');
		$validatorResolver = new $validatorResolverClassName($this->getMock('F3\FLOW3\Object\ManagerInterface'));
		$result = $validatorResolver->_call('parseValidatorAnnotation', $annotation);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * dataProvider for buildBaseValidatorConjunctionAddsCustomValidatorToTheReturnedConjunction
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function modelAndValidatorClassNames() {
		return array(
			array('\FLOW8\Blog\Domain\Validator\BlogValidator', '\FLOW8\Blog\Domain\Model\Blog'),
			array('﻿\Domain\Validator\Content\PageValidator', '﻿\Domain\Model\Content\Page')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndValidatorClassNames
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildBaseValidatorConjunctionAddsCustomValidatorToTheReturnedConjunction($validatorClassName, $modelClassName) {
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$mockConjunctionValidator = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockValidator);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('resolveValidatorObjectName', 'createValidator'), array($mockObjectManager));
		$validatorResolver->expects($this->once())->method('createValidator')->with($validatorClassName)->will($this->returnValue($mockValidator));

		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName);
		$this->assertSame($mockConjunctionValidator, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments() {
		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction'), array(), '', FALSE);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue(array()));

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
		$this->assertSame(array(), $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'type' => 'string'
			),
			'arg2' => array(
				'type' => 'array'
			)

		);
		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2'
			),
			'validate' => array(
				'$arg1 Foo(bar = baz), Bar',
				'$arg2 F3\TestPackage\Quux'
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

		$mockStringValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockArrayValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockFooValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		
		$conjunction1 = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);

		$conjunction2 = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
		$conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction2));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(6))->method('createValidator')->with('F3\TestPackage\Quux')->will($this->returnValue($mockQuuxValidator));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
		$this->assertEquals(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyConjunctionIfNoValidatorIsFoundForClassParameter() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodParameters = array(
			'arg' => array(
				'type' => 'FLOW8\Blog\Domain\Model\Blog'
			)

		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

		$conjunction = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction->expects($this->never())->method('addValidator');

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('FLOW8\Blog\Domain\Validator\BlogValidator')->will($this->returnValue(NULL));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 * @expectedException F3\FLOW3\Validation\Exception\InvalidValidationConfiguration
	 */
	public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'type' => 'string'
			)
		);
		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
			),
			'validate' => array(
				'$arg2 F3\TestPackage\Quux'
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

		$mockStringValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$conjunction1 = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('F3\TestPackage\Quux')->will($this->returnValue($mockQuuxValidator));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction() {
		$mockObject = $this->getMock('stdClass');
		$className = get_class($mockObject);

		$propertyTagsValues = array(
			'foo' => array(
				'var' => array('string'),
				'validate' => array(
					'Foo(bar = baz), Bar',
					'Baz'
				)
			),
			'bar' => array(
				'var' => array('integer'),
				'validate' => array(
					'F3\TestPackage\Quux'
				)
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('foo', 'bar')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
		$mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));

		$mockObjectValidator = $this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE);

		$mockConjunctionValidator = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockObjectValidator);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('resolveValidatorObjectName', 'createValidator'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('F3\TestPackage\Quux')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with($className . 'Validator')->will($this->returnValue(NULL));

		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $className);
		$this->assertSame($mockConjunctionValidator, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValidatorObjectNameCallsUnifyDataType() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('unifyDataType'), array($mockObjectManager));
		$mockValidator->expects($this->once())->method('unifyDataType')->with('someDataType');
		$mockValidator->_call('resolveValidatorObjectName', 'someDataType');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeCorrectlyRenamesPHPDataTypes() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'integer'));
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'int'));
		$this->assertEquals('String', $mockValidator->_call('unifyDataType', 'string'));
		$this->assertEquals('Array', $mockValidator->_call('unifyDataType', 'array'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'float'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'double'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'boolean'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'number'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'numeric'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeRenamesMixedToRaw() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Raw', $mockValidator->_call('unifyDataType', 'mixed'));
	}
}

?>