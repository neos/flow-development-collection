<?php
namespace F3\FLOW3\Tests\Unit\Validation;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ValidatorResolverTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isRegistered')->with('F3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(FALSE));

		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame(FALSE, $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegistered() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame('Foo', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameRemovesALeadingBackslashFromThePassedType() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo\\Bar')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame('Foo\\Bar', $validatorResolver->_call('resolveValidatorObjectName', '\\Foo\\Bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isRegistered')->with('F3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(TRUE));
		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame('F3\FLOW3\Validation\Validator\FooValidator', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions() {
		$className = 'Test' . md5(uniqid(mt_rand(), TRUE));
		eval("class $className implements \F3\FLOW3\Validation\Validator\ValidatorInterface {" . '
				public $validatorOptions;
				public function __construct($validatorOptions) {
					$this->validatorOptions = $validatorOptions;
				}
				public function validate($subject) {}
			}');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getScope')->with($className)->will($this->returnValue(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE));

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver',array('resolveValidatorObjectName'), array($mockObjectManager));
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
		$validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
		$this->assertInstanceOf($className, $validator);
		$this->assertEquals(array('foo' => 'bar'), $validator->validatorOptions);
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildBaseValidatorCachesTheResultOfTheBuildBaseValidatorConjunctionCalls() {
		$mockConjunctionValidator = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));

		$result = $validatorResolver->getBaseValidatorConjunction('F3\Virtual\Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#1');

		$result = $validatorResolver->getBaseValidatorConjunction('F3\Virtual\Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#2');
	}

	/**
	 * data provider for parseValidatorAnnotationCanParseAnnotations
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
			array('$parentObject.propertyName MyValidator', array('argumentName' => 'parentObject.propertyName', 'validators' => array(
						array('validatorName' => 'MyValidator', 'validatorOptions' => array())))),
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
		$validatorResolver = new $validatorResolverClassName($this->getMock('F3\FLOW3\Object\ObjectManagerInterface'));
		$result = $validatorResolver->_call('parseValidatorAnnotation', $annotation);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments() {
		$mockController = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\ActionController', array('fooAction'), array(), '', FALSE);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctionsBuildsNestedValidationRulesSpecifiedInMethodAnnotations() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'type' => '\F3\Package\Model\Foo'
			),
			'arg2' => array(
				'type' => '\F3\Package\Model\Bar'
			)

		);
		$methodTagsValues = array(
			'param' => array(
				'\F3\Package\Model\Foo $arg1',
				'\F3\Package\Model\Bar $arg2'
			),
			'validate' => array(
				'$arg1.sub1a Validator1',
				'$arg2.sub2a.sub2b Validator2'
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockPropertyValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockPropertyValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockFooValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockBarValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);

		$mockObjectValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockObjectValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockObjectValidator2a = $this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);

		$conjunction1 = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockObjectValidator1);

		$conjunction2 = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockBarValidator);
		$conjunction2->expects($this->at(1))->method('addValidator')->with($mockObjectValidator2);

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('\F3\Package\Validator\FooValidator')->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction2));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('\F3\Package\Validator\BarValidator')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Validator1')->will($this->returnValue($mockPropertyValidator1));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('F3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator1));
		$validatorResolver->expects($this->at(6))->method('createValidator')->with('Validator2')->will($this->returnValue($mockPropertyValidator2));
		$validatorResolver->expects($this->at(7))->method('createValidator')->with('F3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator2));
		$validatorResolver->expects($this->at(8))->method('createValidator')->with('F3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator2a));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
#		$this->assertEquals(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException F3\FLOW3\Validation\Exception\InvalidValidationConfigurationException
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildBaseValidatorConjunctionAddsCustomValidatorToTheReturnedConjunction() {
		$modelClassName = 'Page' . md5(uniqid(mt_rand(), TRUE));
		$validatorClassName = 'Domain\Validator\Content\\' . $modelClassName . 'Validator';
		eval('namespace Domain\Model\Content; class ' . $modelClassName . '{}');
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$modelClassName = 'Domain\Model\Content\\' . $modelClassName;

		$mockConjunctionValidator = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockValidator);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$mockReflectionService = $this->getMock('\F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array()));
		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE)));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with($validatorClassName)->will($this->returnValue($mockValidator));

		$validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName);
		$builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
		$this->assertSame($mockConjunctionValidator, $builtValidators[$modelClassName]);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildBaseValidatorConjunctionAddsValidatorsOnlyForPropertiesHoldingPrototypes() {
		$entityClassName = 'Entity' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . '{}');
		$otherClassName = 'Other' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $otherClassName . '{}');
		$modelClassName = 'Model' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $modelClassName . '{}');

		$mockConjunctionValidator = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->at(0))->method('get')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));
		$mockObjectManager->expects($this->at(2))->method('getScope')->with($entityClassName)->will($this->returnValue(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE));
		$mockObjectManager->expects($this->at(4))->method('getScope')->with($otherClassName)->will($this->returnValue(NULL));

		$mockReflectionService = $this->getMock('\F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array('entityProperty', 'otherProperty')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will($this->returnValue(array('var' => array($entityClassName))));
		$mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($modelClassName, 'otherProperty')->will($this->returnValue(array('var' => array($otherClassName))));

		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE)));
		$validatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface')));

		$validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildBaseValidatorConjunctionReturnsNullIfNoValidatorBuilt() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));

		$this->assertNull($validatorResolver->_call('buildBaseValidatorConjunction', 'NonExistingClassName'));
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('foo', 'bar')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
		$mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));

		$mockObjectValidator = $this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE);
		$mockObjectValidator->expects($this->once())->method('getPropertyValidators')->will($this->returnValue(array('dummy')));

		$mockConjunctionValidator = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockObjectValidator);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$validatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->expects($this->at(0))->method('createValidator')->with('F3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('F3\TestPackage\Quux')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with($className . 'Validator')->will($this->returnValue(NULL));

		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $className);
		$builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
		$this->assertSame($mockConjunctionValidator, $builtValidators[$className]);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValidatorObjectNameCallsGetValidatorType() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockValidator = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('getValidatorType'), array($mockObjectManager));
		$mockValidator->expects($this->once())->method('getValidatorType')->with('someDataType');
		$mockValidator->_call('resolveValidatorObjectName', 'someDataType');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValidatorTypeCorrectlyRenamesPhpDataTypes() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockValidatorResolver = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Integer', $mockValidatorResolver->_call('getValidatorType', 'integer'));
		$this->assertEquals('Integer', $mockValidatorResolver->_call('getValidatorType', 'int'));
		$this->assertEquals('String', $mockValidatorResolver->_call('getValidatorType', 'string'));
		$this->assertEquals('Array', $mockValidatorResolver->_call('getValidatorType', 'array'));
		$this->assertEquals('Float', $mockValidatorResolver->_call('getValidatorType', 'float'));
		$this->assertEquals('Float', $mockValidatorResolver->_call('getValidatorType', 'double'));
		$this->assertEquals('Boolean', $mockValidatorResolver->_call('getValidatorType', 'boolean'));
		$this->assertEquals('Boolean', $mockValidatorResolver->_call('getValidatorType', 'bool'));
		$this->assertEquals('Number', $mockValidatorResolver->_call('getValidatorType', 'number'));
		$this->assertEquals('Number', $mockValidatorResolver->_call('getValidatorType', 'numeric'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValidatorTypeRenamesMixedToRaw() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockValidator = $this->getAccessibleMock('F3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Raw', $mockValidator->_call('getValidatorType', 'mixed'));
	}
}

?>