<?php
namespace TYPO3\Flow\Tests\Unit\Validation;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Object\Configuration\Configuration;

/**
 * Testcase for the validator resolver
 *
 */
class ValidatorResolverTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Validation\ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
     */
    protected $mockReflectionService;

    public function setUp()
    {
        $this->mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $this->mockReflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class);

        $this->validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('dummy'));
        $this->inject($this->validatorResolver, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved()
    {
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array('Foo')));

        $this->mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Foo')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with(\TYPO3\Flow\Validation\Validator\FooValidator::class)->will($this->returnValue(false));

        $this->assertSame(false, $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegisteredAndImplementsValidatorInterface()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo')->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array('Foo')));

        $this->assertSame('Foo', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsFalseIfAnObjectOfTheArgumentNameIsRegisteredButDoesNotImplementValidatorInterface()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Foo')->will($this->returnValue(true));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with(\TYPO3\Flow\Validation\Validator\FooValidator::class)->will($this->returnValue(false));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array('Bar')));

        $this->assertFalse($this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsValidatorObjectNameIfAnObjectOfTheArgumentNameIsRegisteredAndDoesNotImplementValidatorInterfaceAndAValidatorForTheObjectExists()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('DateTime')->will($this->returnValue(true));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with(\TYPO3\Flow\Validation\Validator\DateTimeValidator::class)->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array(\TYPO3\Flow\Validation\Validator\DateTimeValidator::class)));

        $this->assertSame(\TYPO3\Flow\Validation\Validator\DateTimeValidator::class, $this->validatorResolver->_call('resolveValidatorObjectName', 'DateTime'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameRemovesALeadingBackslashFromThePassedType()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo\Bar')->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array('Foo\Bar')));

        $this->assertSame('Foo\Bar', $this->validatorResolver->_call('resolveValidatorObjectName', '\Foo\Bar'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShorthandValidatornames()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Mypkg:My')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with('Mypkg\Validation\Validator\MyValidator')->will($this->returnValue(true));

        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array('Mypkg\Validation\Validator\MyValidator')));

        $this->assertSame('Mypkg\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg:My'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShorthandValidatornamesForHierarchicalPackages()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Mypkg.Foo:My')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with('Mypkg\Foo\Validation\Validator\\MyValidator')->will($this->returnValue(true));

        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array('Mypkg\Foo\Validation\Validator\MyValidator')));

        $this->assertSame('Mypkg\Foo\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg.Foo:My'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Foo')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with(\TYPO3\Flow\Validation\Validator\FooValidator::class)->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array(\TYPO3\Flow\Validation\Validator\FooValidator::class)));
        $this->assertSame(\TYPO3\Flow\Validation\Validator\FooValidator::class, $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCallsGetValidatorType()
    {
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($this->mockReflectionService));

        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array()));

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('getValidatorType'));
        $validatorResolver->_set('objectManager', $mockObjectManager);

        $validatorResolver->expects($this->once())->method('getValidatorType')->with('someDataType');
        $validatorResolver->_call('resolveValidatorObjectName', 'someDataType');
    }

    /**
     * @test
     */
    public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions()
    {
        $className = 'Test' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' implements \TYPO3\Flow\Validation\Validator\ValidatorInterface {
				protected $options = array();
				public function __construct(array $options = array()) {
					$this->options = $options;
				}
				public function validate($subject) {}
				public function getOptions() { return $this->options; }
			}');
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('getScope')->with($className)->will($this->returnValue(Configuration::SCOPE_PROTOTYPE));

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('resolveValidatorObjectName'));
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
        $validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
        $this->assertInstanceOf($className, $validator);
        $this->assertEquals(array('foo' => 'bar'), $validator->getOptions());
    }

    /**
     * @test
     */
    public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved()
    {
        $validatorResolver = $this->getMockBuilder(\TYPO3\Flow\Validation\ValidatorResolver::class)->setMethods(array('resolveValidatorObjectName'))->getMock();
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->will($this->returnValue(false));
        $validator = $validatorResolver->createValidator('Foo', array('foo' => 'bar'));
        $this->assertNull($validator);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationConfigurationException
     */
    public function createValidatorThrowsExceptionForSingletonValidatorsWithOptions()
    {
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getScope')->with('FooType')->will($this->returnValue(Configuration::SCOPE_SINGLETON));

        $validatorResolver = $this->getMockBuilder(\TYPO3\Flow\Validation\ValidatorResolver::class)->setMethods(array('resolveValidatorObjectName'))->getMock();
        $this->inject($validatorResolver, 'objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('FooType')->will($this->returnValue('FooType'));
        $validatorResolver->createValidator('FooType', array('foo' => 'bar'));
    }

    /**
     * @test
     */
    public function buildBaseValidatorCachesTheResultOfTheBuildBaseValidatorConjunctionCalls()
    {
        $mockReflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->at(0))->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array()));
        $mockReflectionService->expects($this->at(1))->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class)->will($this->returnValue(array()));
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockReflectionService));
        $this->validatorResolver->_set('objectManager', $mockObjectManager);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result1 = $this->validatorResolver->getBaseValidatorConjunction(\TYPO3\Virtual\Foo::class);
        $this->assertInstanceOf(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class, $result1, '#1');

        $result2 = $this->validatorResolver->getBaseValidatorConjunction(\TYPO3\Virtual\Foo::class);
        $this->assertSame($result1, $result2, '#2');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments()
    {
        $mockController = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\ActionController::class, array('fooAction'), array(), '', false);

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue(array()));

        $this->validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('createValidator'), array(), '', false);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
        $this->assertSame(array(), $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod()
    {
        $mockObject = new \stdClass();

        $methodParameters = array(
            'arg1' => array(
                'type' => 'string'
            ),
            'arg2' => array(
                'type' => 'array'
            )

        );
        $validateAnnotations = array(
            new \TYPO3\Flow\Annotations\Validate(array(
                'type' => 'Foo',
                'options' => array('bar' => 'baz'),
                'argumentName' => '$arg1'
            )),
            new \TYPO3\Flow\Annotations\Validate(array(
                'type' => 'Bar',
                'argumentName' => '$arg1'
            )),
            new \TYPO3\Flow\Annotations\Validate(array(
                'type' => \TYPO3\TestPackage\Quux::class,
                'argumentName' => '$arg2'
            )),
        );

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue($validateAnnotations));

        $mockStringValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $mockArrayValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $mockFooValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $mockBarValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);

        $conjunction1 = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
        $conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
        $conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);

        $conjunction2 = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
        $conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('createValidator'), array(), '', false);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction2));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
        $validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
        $validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
        $validatorResolver->expects($this->at(6))->method('createValidator')->with(\TYPO3\TestPackage\Quux::class)->will($this->returnValue($mockQuuxValidator));

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
        $this->assertEquals(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyConjunctionIfNoValidatorIsFoundForMethodParameter()
    {
        $mockObject = new \stdClass();

        $methodParameters = array(
            'arg' => array(
                'type' => 'FLOW8\Blog\Domain\Model\Blog'
            )
        );

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue(array()));

        $conjunction = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction->expects($this->never())->method('addValidator');

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('createValidator'), array(), '', false);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction));

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationConfigurationException
     */
    public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists()
    {
        $mockObject = new \stdClass();

        $methodParameters = array(
            'arg1' => array(
                'type' => 'string'
            )
        );
        $validateAnnotations = array(
            new \TYPO3\Flow\Annotations\Validate(array(
                'type' => \TYPO3\TestPackage\Quux::class,
                'argumentName' => '$arg2'
            )),
        );

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue($validateAnnotations));
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

        $mockStringValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $conjunction1 = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('createValidator'), array(), '', false);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with(\TYPO3\TestPackage\Quux::class)->will($this->returnValue($mockQuuxValidator));

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionAddsCustomValidatorToTheReturnedConjunction()
    {
        $modelClassName = 'Page' . md5(uniqid(mt_rand(), true));
        $validatorClassName = 'Domain\Validator\Content\\' . $modelClassName . 'Validator';
        eval('namespace Domain\Model\Content; class ' . $modelClassName . '{}');

        $modelClassName = 'Domain\Model\Content\\' . $modelClassName;

        $mockReflectionService = $this->createMock('\TYPO3\Flow\Reflection\ReflectionService');
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array()));
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('resolveValidatorObjectName', 'createValidator'));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('createValidator')->with($validatorClassName)->will($this->returnValue(new \TYPO3\Flow\Validation\Validator\EmailAddressValidator()));
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class)->will($this->returnValue(array()));

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, array('Default'));
        $builtValidators = $validatorResolver->_get('baseValidatorConjunctions');

        $this->assertFalse($builtValidators[$modelClassName]->validate('foo@example.com')->hasErrors());
        $this->assertTrue($builtValidators[$modelClassName]->validate('foo')->hasErrors());
    }

    /**
     * @test
     */
    public function addCustomValidatorsAddsExpectedPolyTypeValidatorToTheConjunction()
    {
        $highPriorityValidatorClassName = 'RandomHighPrio' . md5(uniqid(mt_rand(), true)) . 'PolyTypeValidator';
        $lowPriorityValidatorClassName = 'RandomLowPrio' . md5(uniqid(mt_rand(), true)) . 'PolyTypeValidator';
        $modelClassName = 'Acme\Test\Content\Page' . md5(uniqid(mt_rand(), true));

        $mockLowPriorityValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class, array(), array(), $lowPriorityValidatorClassName);
        $mockLowPriorityValidator->expects($this->atLeastOnce())->method('canValidate')->with($modelClassName)->will($this->returnValue(true));
        $mockLowPriorityValidator->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(100));
        $mockHighPriorityValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class, array(), array(), $highPriorityValidatorClassName);
        $mockHighPriorityValidator->expects($this->atLeastOnce())->method('canValidate')->with($modelClassName)->will($this->returnValue(true));
        $mockHighPriorityValidator->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(200));

        $mockConjunctionValidator = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class)->setMethods(array('addValidator'))->getMock();
        $mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockHighPriorityValidator);

        $mockReflectionService = $this->createMock('\TYPO3\Flow\Reflection\ReflectionService');
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class)->will($this->returnValue(array($highPriorityValidatorClassName, $lowPriorityValidatorClassName)));
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('createValidator'));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->at(0))->method('createValidator')->will($this->returnValue(null));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with($highPriorityValidatorClassName)->will($this->returnValue($mockHighPriorityValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with($lowPriorityValidatorClassName)->will($this->returnValue($mockLowPriorityValidator));

        $validatorResolver->_callRef('addCustomValidators', $modelClassName, $mockConjunctionValidator);
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionAddsValidatorsOnlyForPropertiesHoldingPrototypes()
    {
        $entityClassName = 'Entity' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . '{}');
        $otherClassName = 'Other' . md5(uniqid(mt_rand(), true));
        eval('class ' . $otherClassName . '{}');
        $modelClassName = 'Model' . md5(uniqid(mt_rand(), true));
        eval('class ' . $modelClassName . '{}');

        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(true));
        $mockObjectManager->expects($this->at(1))->method('getScope')->with($entityClassName)->will($this->returnValue(Configuration::SCOPE_PROTOTYPE));
        $mockObjectManager->expects($this->at(3))->method('getScope')->with($otherClassName)->will($this->returnValue(null));

        $mockReflectionService = $this->createMock('\TYPO3\Flow\Reflection\ReflectionService');
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class)->will($this->returnValue(array()));
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array('entityProperty', 'otherProperty')));
        $mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will($this->returnValue(array('var' => array($entityClassName))));
        $mockReflectionService->expects($this->at(2))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(3))->method('getPropertyAnnotations')->with($modelClassName, 'entityProperty', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue(array()));
        $mockReflectionService->expects($this->at(4))->method('getPropertyTagsValues')->with($modelClassName, 'otherProperty')->will($this->returnValue(array('var' => array($otherClassName))));
        $mockReflectionService->expects($this->at(5))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(6))->method('getPropertyAnnotations')->with($modelClassName, 'otherProperty', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue(array()));

        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction'));
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->will($this->returnValue($this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)));

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, array('Default'));
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionSkipsPropertiesAnnotatedWithIgnoreValidation()
    {
        $modelClassName = 'Model' . md5(uniqid(mt_rand(), true));
        eval('class ' . $modelClassName . '{}');

        $mockReflectionService = $this->createMock('\TYPO3\Flow\Reflection\ReflectionService');
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->will($this->returnValue(array()));
        $mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->will($this->returnValue(array('entityProperty')));
        $mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will($this->returnValue(array('var' => array('ToBeIgnored'))));
        $mockReflectionService->expects($this->at(2))->method('isPropertyAnnotatedWith')->with($modelClassName, 'entityProperty', \TYPO3\Flow\Annotations\IgnoreValidation::class)->will($this->returnValue(true));
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($mockReflectionService));

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction'));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->never())->method('getBaseValidatorConjunction');

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, array('Default'));
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionReturnsNullIfNoValidatorBuilt()
    {
        $mockReflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->at(0))->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class)->will($this->returnValue(array()));
        $mockReflectionService->expects($this->at(1))->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class)->will($this->returnValue(array()));
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('dummy'));
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $this->assertNull($validatorResolver->_call('buildBaseValidatorConjunction', 'NonExistingClassName', 'NonExistingClassName', array('Default')));
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction()
    {
        $mockObject = $this->createMock(\stdClass::class);
        $className = get_class($mockObject);

        $propertyTagsValues = array(
            'foo' => array(
                'var' => array('string'),
            ),
            'bar' => array(
                'var' => array('integer'),
            ),
            'baz' => array(
                'var' => array('array<TYPO3\TestPackage\Quux>')
            )
        );
        $validateAnnotations = array(
            'foo' => array(
                new \TYPO3\Flow\Annotations\Validate(array(
                    'type' => 'Foo',
                    'options' => array('bar' => 'baz'),
                )),
                new \TYPO3\Flow\Annotations\Validate(array(
                    'type' => 'Bar',
                )),
                new \TYPO3\Flow\Annotations\Validate(array(
                    'type' => 'Baz',
                )),
            ),
            'bar' => array(
                new \TYPO3\Flow\Annotations\Validate(array(
                    'type' => \TYPO3\TestPackage\Quux::class,
                )),
            ),
        );

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class)->will($this->returnValue(array()));
        $mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('foo', 'bar', 'baz')));
        $mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
        $mockReflectionService->expects($this->at(2))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(3))->method('getPropertyAnnotations')->with(get_class($mockObject), 'foo', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue($validateAnnotations['foo']));
        $mockReflectionService->expects($this->at(4))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));
        $mockReflectionService->expects($this->at(5))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(6))->method('getPropertyAnnotations')->with(get_class($mockObject), 'bar', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue($validateAnnotations['bar']));
        $mockReflectionService->expects($this->at(7))->method('getPropertyTagsValues')->with($className, 'baz')->will($this->returnValue($propertyTagsValues['baz']));
        $mockReflectionService->expects($this->at(8))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(9))->method('getPropertyAnnotations')->with(get_class($mockObject), 'baz', \TYPO3\Flow\Annotations\Validate::class)->will($this->returnValue(array()));
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($mockReflectionService));

        $mockObjectValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\GenericObjectValidator::class);

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('resolveValidatorObjectName', 'createValidator'));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        $validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with(\TYPO3\TestPackage\Quux::class)->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(4))->method('createValidator')->with(\TYPO3\Flow\Validation\Validator\CollectionValidator::class, array('elementType' => \TYPO3\TestPackage\Quux::class, 'validationGroups' => array('Default')))->will($this->returnValue($mockObjectValidator));

        $validatorResolver->_call('buildBaseValidatorConjunction', $className . 'Default', $className, array('Default'));
        $builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
        $this->assertInstanceOf(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class, $builtValidators[$className . 'Default']);
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionBuildsCorrectValidationChainForCyclicRelations()
    {
        $fooMockObject = $this->getMockBuilder(\stdClass::class)->setMockClassName('FooMock')->getMock();
        $fooClassName = get_class($fooMockObject);
        $barMockObject = $this->getMockBuilder(\stdClass::class)->setMockClassName('BarMock')->getMock();
        $barClassName = get_class($barMockObject);

        $fooPropertyTagsValues = array(
            'bar' => array(
                'var' => array($barClassName),
            )
        );
        $barPropertyTagsValues = array(
            'foo' => array(
                'var' => array($fooClassName),
            )
        );

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(\TYPO3\Flow\Validation\Validator\PolyTypeObjectValidatorInterface::class)->will($this->returnValue(array()));
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValueMap(array(
            array($fooClassName, array('bar')),
            array($barClassName, array('foo'))
        )));
        $mockReflectionService->expects($this->any())->method('getPropertyTagsValues')->will($this->returnValueMap(array(
            array($fooClassName, 'bar', $fooPropertyTagsValues['bar']),
            array($barClassName, 'foo', $barPropertyTagsValues['foo'])
        )));
        $mockReflectionService->expects($this->any())->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->any())->method('getPropertyAnnotations')->will($this->returnValue(array()));

        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(true));
        $mockObjectManager->expects($this->any())->method('getScope')->will($this->returnValue(\TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE));
        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($mockReflectionService));

        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('resolveValidatorObjectName', 'createValidator'));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        /* @var $validatorChain \TYPO3\Flow\Validation\Validator\ConjunctionValidator */
        $validatorChain = $validatorResolver->getBaseValidatorConjunction($fooClassName);
        $fooValidators = $validatorChain->getValidators();
        $this->assertGreaterThan(0, $fooValidators->count());

        // ugh, it's so cumbersome to work with SplObjectStorage outside of iterations...
        $fooValidators->rewind();
        $barValidators = $fooValidators->current()->getPropertyValidators('bar');
        $this->assertGreaterThan(0, $barValidators->count());

        $barValidators->rewind();
        $barValidators = $barValidators->current()->getValidators();
        $this->assertGreaterThan(0, $barValidators->count());
        $barValidators->rewind();

        $this->assertGreaterThan(0, $barValidators->current()->getPropertyValidators('foo')->count());
    }

    /**
     * @test
     */
    public function getValidatorTypeCorrectlyRenamesPhpDataTypes()
    {
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('dummy'));
        $validatorResolver->_set('objectManager', $mockObjectManager);

        $this->assertEquals('Integer', $validatorResolver->_call('getValidatorType', 'integer'));
        $this->assertEquals('Integer', $validatorResolver->_call('getValidatorType', 'int'));
        $this->assertEquals('String', $validatorResolver->_call('getValidatorType', 'string'));
        $this->assertEquals('Array', $validatorResolver->_call('getValidatorType', 'array'));
        $this->assertEquals('Float', $validatorResolver->_call('getValidatorType', 'float'));
        $this->assertEquals('Float', $validatorResolver->_call('getValidatorType', 'double'));
        $this->assertEquals('Boolean', $validatorResolver->_call('getValidatorType', 'boolean'));
        $this->assertEquals('Boolean', $validatorResolver->_call('getValidatorType', 'bool'));
        $this->assertEquals('Number', $validatorResolver->_call('getValidatorType', 'number'));
        $this->assertEquals('Number', $validatorResolver->_call('getValidatorType', 'numeric'));
    }

    /**
     * @test
     */
    public function getValidatorTypeRenamesMixedToRaw()
    {
        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('dummy'));
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $this->assertEquals('Raw', $validatorResolver->_call('getValidatorType', 'mixed'));
    }

    /**
     * @test
     */
    public function resetEmptiesBaseValidatorConjunctions()
    {
        $validatorResolver = $this->getAccessibleMock(\TYPO3\Flow\Validation\ValidatorResolver::class, array('dummy'));
        $mockConjunctionValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ConjunctionValidator::class);
        $validatorResolver->_set('baseValidatorConjunctions', array('SomeId##' => $mockConjunctionValidator));

        $validatorResolver->reset();
        $this->assertEmpty($validatorResolver->_get('baseValidatorConjunctions'));
    }
}
