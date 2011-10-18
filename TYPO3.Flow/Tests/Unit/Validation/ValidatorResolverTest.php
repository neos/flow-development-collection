<?php
namespace TYPO3\FLOW3\Tests\Unit\Validation;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the validator resolver
 *
 */
class ValidatorResolverTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isRegistered')->with('TYPO3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(FALSE));

		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame(FALSE, $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegistered() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame('Foo', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameRemovesALeadingBackslashFromThePassedType() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo\\Bar')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame('Foo\\Bar', $validatorResolver->_call('resolveValidatorObjectName', '\\Foo\\Bar'));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isRegistered')->with('TYPO3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(TRUE));
		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));
		$this->assertSame('TYPO3\FLOW3\Validation\Validator\FooValidator', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 */
	public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions() {
		$className = 'Test' . md5(uniqid(mt_rand(), TRUE));
		eval("class $className implements \TYPO3\FLOW3\Validation\Validator\ValidatorInterface {" . '
				public $validatorOptions;
				public function __construct($validatorOptions) {
					$this->validatorOptions = $validatorOptions;
				}
				public function validate($subject) {}
			}');
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getScope')->with($className)->will($this->returnValue(\TYPO3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE));

		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver',array('resolveValidatorObjectName'), array($mockObjectManager));
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
		$validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
		$this->assertInstanceOf($className, $validator);
		$this->assertEquals(array('foo' => 'bar'), $validator->validatorOptions);
	}

	/**
	 * @test
	 */
	public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved() {
		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver',array('resolveValidatorObjectName'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->will($this->returnValue(FALSE));
		$validator = $validatorResolver->createValidator('Foo', array('foo' => 'bar'));
		$this->assertNull($validator);
	}

	/**
	 * @test
	 */
	public function buildBaseValidatorCachesTheResultOfTheBuildBaseValidatorConjunctionCalls() {
		$mockConjunctionValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));

		$result = $validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#1');

		$result = $validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#2');
	}

	/**
	 * @test
	 */
	public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments() {
		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Controller\ActionController', array('fooAction'), array(), '', FALSE);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue(array()));

		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
		$this->assertSame(array(), $result);
	}

	/**
	 * @test
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
		$validateAnnotations = array(
			new \TYPO3\FLOW3\Annotations\Validate(array(
				'type' => 'Foo',
				'options' => array('bar' => 'baz'),
				'argumentName' => '$arg1'
			)),
			new \TYPO3\FLOW3\Annotations\Validate(array(
				'type' => 'Bar',
				'argumentName' => '$arg1'
			)),
			new \TYPO3\FLOW3\Annotations\Validate(array(
				'type' => 'TYPO3\TestPackage\Quux',
				'argumentName' => '$arg2'
			)),
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
		$mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue($validateAnnotations));

		$mockStringValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockArrayValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockFooValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);

		$conjunction1 = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);

		$conjunction2 = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
		$conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);

		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction2));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(6))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will($this->returnValue($mockQuuxValidator));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
		$this->assertEquals(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
	}

	/**
	 * @test
	 */
	public function buildMethodArgumentsValidatorConjunctionsBuildsNestedValidationRulesSpecifiedInMethodAnnotations() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'type' => '\TYPO3\Package\Model\Foo'
			),
			'arg2' => array(
				'type' => '\TYPO3\Package\Model\Bar'
			)

		);
		$validateAnnotations = array(
			new \TYPO3\FLOW3\Annotations\Validate(array(
				'type' => 'Validator1',
				'argumentName' => '$arg1.sub1a'
			)),
			new \TYPO3\FLOW3\Annotations\Validate(array(
				'type' => 'Validator2',
				'argumentName' => '$arg2.sub2a.sub2b'
			)),
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
		$mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue($validateAnnotations));

		$mockPropertyValidator1 = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockPropertyValidator2 = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockFooValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockBarValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);

		$mockObjectValidator1 = $this->getMock('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockObjectValidator2 = $this->getMock('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockObjectValidator2a = $this->getMock('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);

		$conjunction1 = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockObjectValidator1);

		$conjunction2 = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), 'v' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockBarValidator);
		$conjunction2->expects($this->at(1))->method('addValidator')->with($mockObjectValidator2);

		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('\TYPO3\Package\Validator\FooValidator')->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction2));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('\TYPO3\Package\Validator\BarValidator')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Validator1')->will($this->returnValue($mockPropertyValidator1));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator1));
		$validatorResolver->expects($this->at(6))->method('createValidator')->with('Validator2')->will($this->returnValue($mockPropertyValidator2));
		$validatorResolver->expects($this->at(7))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator2));
		$validatorResolver->expects($this->at(8))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator2a));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
		$this->assertEquals(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
	}

	/**
	 * @test
	 */
	public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyConjunctionIfNoValidatorIsFoundForMethodParameter() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodParameters = array(
			'arg' => array(
				'type' => 'FLOW8\Blog\Domain\Model\Blog'
			)
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
		$mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue(array()));

		$conjunction = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction->expects($this->never())->method('addValidator');

		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('FLOW8\Blog\Domain\Validator\BlogValidator')->will($this->returnValue(NULL));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Validation\Exception\InvalidValidationConfigurationException
	 */
	public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'type' => 'string'
			)
		);
		$validateAnnotations = array(
			new \TYPO3\FLOW3\Annotations\Validate(array(
				'type' => 'TYPO3\TestPackage\Quux',
				'argumentName' => '$arg2'
			)),
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue($validateAnnotations));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

		$mockStringValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$conjunction1 = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);

		$validatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will($this->returnValue($mockQuuxValidator));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
	}

	/**
	 * @test
	 */
	public function buildBaseValidatorConjunctionAddsCustomValidatorToTheReturnedConjunction() {
		$modelClassName = 'Page' . md5(uniqid(mt_rand(), TRUE));
		$validatorClassName = 'Domain\Validator\Content\\' . $modelClassName . 'Validator';
		eval('namespace Domain\Model\Content; class ' . $modelClassName . '{}');
		$mockValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface');
		$modelClassName = 'Domain\Model\Content\\' . $modelClassName;

		$mockConjunctionValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockValidator);

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$mockReflectionService = $this->getMock('\TYPO3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array()));
		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($this->getMock('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE)));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with($validatorClassName)->will($this->returnValue($mockValidator));

		$validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName);
		$builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
		$this->assertSame($mockConjunctionValidator, $builtValidators[$modelClassName]);
	}

	/**
	 * @test
	 */
	public function buildBaseValidatorConjunctionAddsValidatorsOnlyForPropertiesHoldingPrototypes() {
		$entityClassName = 'Entity' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . '{}');
		$otherClassName = 'Other' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $otherClassName . '{}');
		$modelClassName = 'Model' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $modelClassName . '{}');

		$mockConjunctionValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));
		$mockObjectManager->expects($this->at(2))->method('getScope')->with($entityClassName)->will($this->returnValue(\TYPO3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE));
		$mockObjectManager->expects($this->at(4))->method('getScope')->with($otherClassName)->will($this->returnValue(NULL));

		$mockReflectionService = $this->getMock('\TYPO3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array('entityProperty', 'otherProperty')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will($this->returnValue(array('var' => array($entityClassName))));
		$mockReflectionService->expects($this->at(2))->method('getPropertyAnnotations')->with($modelClassName, 'entityProperty', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->at(3))->method('getPropertyTagsValues')->with($modelClassName, 'otherProperty')->will($this->returnValue(array('var' => array($otherClassName))));
		$mockReflectionService->expects($this->at(4))->method('getPropertyAnnotations')->with($modelClassName, 'otherProperty', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue(array()));

		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($this->getMock('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE)));
		$validatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->will($this->returnValue($this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface')));

		$validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName);
	}

	/**
	 * @test
	 */
	public function buildBaseValidatorConjunctionReturnsNullIfNoValidatorBuilt() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager));

		$this->assertNull($validatorResolver->_call('buildBaseValidatorConjunction', 'NonExistingClassName'));
	}

	/**
	 * @test
	 */
	public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction() {
		$mockObject = $this->getMock('stdClass');
		$className = get_class($mockObject);

		$propertyTagsValues = array(
			'foo' => array(
				'var' => array('string'),
			),
			'bar' => array(
				'var' => array('integer'),
			)
		);
		$validateAnnotations = array(
			'foo' => array(
				new \TYPO3\FLOW3\Annotations\Validate(array(
					'type' => 'Foo',
					'options' => array('bar' => 'baz'),
				)),
				new \TYPO3\FLOW3\Annotations\Validate(array(
					'type' => 'Bar',
				)),
				new \TYPO3\FLOW3\Annotations\Validate(array(
					'type' => 'Baz',
				)),
			),
			'bar' => array(
				new \TYPO3\FLOW3\Annotations\Validate(array(
					'type' => 'TYPO3\TestPackage\Quux',
				)),
			),
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('foo', 'bar')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
		$mockReflectionService->expects($this->at(2))->method('getPropertyAnnotations')->with(get_class($mockObject), 'foo', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue($validateAnnotations['foo']));
		$mockReflectionService->expects($this->at(3))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));
		$mockReflectionService->expects($this->at(4))->method('getPropertyAnnotations')->with(get_class($mockObject), 'bar', 'TYPO3\FLOW3\Annotations\Validate')->will($this->returnValue($validateAnnotations['bar']));

		$mockObjectValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE);
		$mockObjectValidator->expects($this->once())->method('getPropertyValidators')->will($this->returnValue(array('dummy')));

		$mockConjunctionValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockObjectValidator);

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$validatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->expects($this->at(0))->method('createValidator')->with('TYPO3\FLOW3\Validation\Validator\GenericObjectValidator')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with($className . 'Validator')->will($this->returnValue(NULL));

		$validatorResolver->_call('buildBaseValidatorConjunction', $className);
		$builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
		$this->assertSame($mockConjunctionValidator, $builtValidators[$className]);
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameCallsGetValidatorType() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockValidator = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('getValidatorType'), array($mockObjectManager));
		$mockValidator->expects($this->once())->method('getValidatorType')->with('someDataType');
		$mockValidator->_call('resolveValidatorObjectName', 'someDataType');
	}

	/**
	 * @test
	 */
	public function getValidatorTypeCorrectlyRenamesPhpDataTypes() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockValidatorResolver = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager), '', FALSE);
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
	 */
	public function getValidatorTypeRenamesMixedToRaw() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockValidator = $this->getAccessibleMock('TYPO3\FLOW3\Validation\ValidatorResolver', array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Raw', $mockValidator->_call('getValidatorType', 'mixed'));
	}
}

?>