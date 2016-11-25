<?php
namespace Neos\Flow\Tests\Unit\Validation;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Validator\CollectionValidator;
use Neos\Flow\Validation\Validator\ConjunctionValidator;
use Neos\Flow\Validation\Validator\DateTimeValidator;
use Neos\Flow\Validation\Validator\EmailAddressValidator;
use Neos\Flow\Validation\Validator\GenericObjectValidator;
use Neos\Flow\Validation\Validator\PolyTypeObjectValidatorInterface;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Flow\Annotations;

/**
 * Testcase for the validator resolver
 *
 */
class ValidatorResolverTest extends UnitTestCase
{
    /**
     * @var ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    public function setUp()
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockReflectionService = $this->createMock(ReflectionService::class);

        $this->validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
        $this->inject($this->validatorResolver, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved()
    {
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue(['Foo']));

        $this->mockObjectManager->expects($this->at(0))->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Foo')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with('Neos\Flow\Validation\Validator\FooValidator')->will($this->returnValue(false));

        $this->assertSame(false, $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegisteredAndImplementsValidatorInterface()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo')->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue(['Foo']));

        $this->assertSame('Foo', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsFalseIfAnObjectOfTheArgumentNameIsRegisteredButDoesNotImplementValidatorInterface()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Foo')->will($this->returnValue(true));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with('Neos\Flow\Validation\Validator\FooValidator')->will($this->returnValue(false));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue(['Bar']));

        $this->assertFalse($this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsValidatorObjectNameIfAnObjectOfTheArgumentNameIsRegisteredAndDoesNotImplementValidatorInterfaceAndAValidatorForTheObjectExists()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('DateTime')->will($this->returnValue(true));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with(DateTimeValidator::class)->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue([DateTimeValidator::class]));

        $this->assertSame(DateTimeValidator::class, $this->validatorResolver->_call('resolveValidatorObjectName', 'DateTime'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameRemovesALeadingBackslashFromThePassedType()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->any())->method('isRegistered')->with('Foo\Bar')->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue(['Foo\Bar']));

        $this->assertSame('Foo\Bar', $this->validatorResolver->_call('resolveValidatorObjectName', '\Foo\Bar'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShorthandValidatornames()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Mypkg:My')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with('Mypkg\Validation\Validator\MyValidator')->will($this->returnValue(true));

        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue(['Mypkg\Validation\Validator\MyValidator']));

        $this->assertSame('Mypkg\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg:My'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShorthandValidatornamesForHierarchicalPackages()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Mypkg.Foo:My')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with('Mypkg\Foo\Validation\Validator\\MyValidator')->will($this->returnValue(true));

        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue(['Mypkg\Foo\Validation\Validator\MyValidator']));

        $this->assertSame('Mypkg\Foo\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg.Foo:My'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators()
    {
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects($this->at(1))->method('isRegistered')->with('Foo')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(2))->method('isRegistered')->with('Neos\Flow\Validation\Validator\FooValidator')->will($this->returnValue(true));
        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue(['Neos\Flow\Validation\Validator\FooValidator']));
        $this->assertSame('Neos\Flow\Validation\Validator\FooValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCallsGetValidatorType()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($this->mockReflectionService));

        $this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue([]));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['getValidatorType']);
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
        eval('class ' . $className . ' implements \Neos\Flow\Validation\Validator\ValidatorInterface {
				protected $options = array();
				public function __construct(array $options = array()) {
					$this->options = $options;
				}
				public function validate($subject) {}
				public function getOptions() { return $this->options; }
			}');
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('getScope')->with($className)->will($this->returnValue(Configuration::SCOPE_PROTOTYPE));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
        $validator = $validatorResolver->createValidator($className, ['foo' => 'bar']);
        $this->assertInstanceOf($className, $validator);
        $this->assertEquals(['foo' => 'bar'], $validator->getOptions());
    }

    /**
     * @test
     */
    public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved()
    {
        $validatorResolver = $this->getMockBuilder(ValidatorResolver::class)->setMethods(['resolveValidatorObjectName'])->getMock();
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->will($this->returnValue(false));
        $validator = $validatorResolver->createValidator('Foo', ['foo' => 'bar']);
        $this->assertNull($validator);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationConfigurationException
     */
    public function createValidatorThrowsExceptionForSingletonValidatorsWithOptions()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getScope')->with('FooType')->will($this->returnValue(Configuration::SCOPE_SINGLETON));

        $validatorResolver = $this->getMockBuilder(ValidatorResolver::class)->setMethods(['resolveValidatorObjectName'])->getMock();
        $this->inject($validatorResolver, 'objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('FooType')->will($this->returnValue('FooType'));
        $validatorResolver->createValidator('FooType', ['foo' => 'bar']);
    }

    /**
     * @test
     */
    public function buildBaseValidatorCachesTheResultOfTheBuildBaseValidatorConjunctionCalls()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->at(0))->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(1))->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will($this->returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockReflectionService));
        $this->validatorResolver->_set('objectManager', $mockObjectManager);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result1 = $this->validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
        $this->assertInstanceOf(ConjunctionValidator::class, $result1, '#1');

        $result2 = $this->validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
        $this->assertSame($result1, $result2, '#2');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments()
    {
        $mockController = $this->getAccessibleMock(ActionController::class, ['fooAction'], [], '', false);

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue([]));

        $this->validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
        $this->assertSame([], $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod()
    {
        $mockObject = new \stdClass();

        $methodParameters = [
            'arg1' => [
                'type' => 'string'
            ],
            'arg2' => [
                'type' => 'array'
            ]

        ];
        $validateAnnotations = [
            new Annotations\Validate([
                'type' => 'Foo',
                'options' => ['bar' => 'baz'],
                'argumentName' => '$arg1'
            ]),
            new Annotations\Validate([
                'type' => 'Bar',
                'argumentName' => '$arg1'
            ]),
            new Annotations\Validate([
                'type' => 'TYPO3\TestPackage\Quux',
                'argumentName' => '$arg2'
            ]),
        ];

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Annotations\Validate::class)->will($this->returnValue($validateAnnotations));

        $mockStringValidator = $this->createMock(ValidatorInterface::class);
        $mockArrayValidator = $this->createMock(ValidatorInterface::class);
        $mockFooValidator = $this->createMock(ValidatorInterface::class);
        $mockBarValidator = $this->createMock(ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(ValidatorInterface::class);

        $conjunction1 = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
        $conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
        $conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);

        $conjunction2 = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
        $conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with(ConjunctionValidator::class)->will($this->returnValue($conjunction2));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
        $validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will($this->returnValue($mockFooValidator));
        $validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
        $validatorResolver->expects($this->at(6))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will($this->returnValue($mockQuuxValidator));

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
        $this->assertEquals(['arg1' => $conjunction1, 'arg2' => $conjunction2], $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyConjunctionIfNoValidatorIsFoundForMethodParameter()
    {
        $mockObject = new \stdClass();

        $methodParameters = [
            'arg' => [
                'type' => 'FLOW8\Blog\Domain\Model\Blog'
            ]
        ];

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Annotations\Validate::class)->will($this->returnValue([]));

        $conjunction = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction->expects($this->never())->method('addValidator');

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(ConjunctionValidator::class)->will($this->returnValue($conjunction));

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationConfigurationException
     */
    public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists()
    {
        $mockObject = new \stdClass();

        $methodParameters = [
            'arg1' => [
                'type' => 'string'
            ]
        ];
        $validateAnnotations = [
            new Annotations\Validate([
                'type' => 'TYPO3\TestPackage\Quux',
                'argumentName' => '$arg2'
            ]),
        ];

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Annotations\Validate::class)->will($this->returnValue($validateAnnotations));
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

        $mockStringValidator = $this->createMock(ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(ValidatorInterface::class);
        $conjunction1 = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will($this->returnValue($mockQuuxValidator));

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

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('createValidator')->with($validatorClassName)->will($this->returnValue(new EmailAddressValidator()));
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will($this->returnValue([]));

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
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

        $mockLowPriorityValidator = $this->createMock(PolyTypeObjectValidatorInterface::class, [], [], $lowPriorityValidatorClassName);
        $mockLowPriorityValidator->expects($this->atLeastOnce())->method('canValidate')->with($modelClassName)->will($this->returnValue(true));
        $mockLowPriorityValidator->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(100));
        $mockHighPriorityValidator = $this->createMock(PolyTypeObjectValidatorInterface::class, [], [], $highPriorityValidatorClassName);
        $mockHighPriorityValidator->expects($this->atLeastOnce())->method('canValidate')->with($modelClassName)->will($this->returnValue(true));
        $mockHighPriorityValidator->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(200));

        $mockConjunctionValidator = $this->getMockBuilder(ConjunctionValidator::class)->setMethods(['addValidator'])->getMock();
        $mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockHighPriorityValidator);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will($this->returnValue([$highPriorityValidatorClassName, $lowPriorityValidatorClassName]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator']);
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

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(true));
        $mockObjectManager->expects($this->at(1))->method('getScope')->with($entityClassName)->will($this->returnValue(Configuration::SCOPE_PROTOTYPE));
        $mockObjectManager->expects($this->at(3))->method('getScope')->with($otherClassName)->will($this->returnValue(null));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will($this->returnValue([]));
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(['entityProperty', 'otherProperty']));
        $mockReflectionService->expects($this->at(1))->method('getClassSchema')->will($this->returnValue(null));
        $mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will($this->returnValue(['var' => [$entityClassName]]));
        $mockReflectionService->expects($this->at(3))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(4))->method('getPropertyAnnotations')->with($modelClassName, 'entityProperty', Annotations\Validate::class)->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(5))->method('getPropertyTagsValues')->with($modelClassName, 'otherProperty')->will($this->returnValue(['var' => [$otherClassName]]));
        $mockReflectionService->expects($this->at(6))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(7))->method('getPropertyAnnotations')->with($modelClassName, 'otherProperty', Annotations\Validate::class)->will($this->returnValue([]));

        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->will($this->returnValue($this->createMock(ValidatorInterface::class)));

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionSkipsPropertiesAnnotatedWithIgnoreValidation()
    {
        $modelClassName = 'Model' . md5(uniqid(mt_rand(), true));
        eval('class ' . $modelClassName . '{}');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(0))->method('getClassSchema')->will($this->returnValue(null));
        $mockReflectionService->expects($this->at(1))->method('getClassPropertyNames')->will($this->returnValue(['entityProperty']));
        $mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will($this->returnValue(['var' => ['ToBeIgnored']]));
        $mockReflectionService->expects($this->at(3))->method('isPropertyAnnotatedWith')->with($modelClassName, 'entityProperty', Annotations\IgnoreValidation::class)->will($this->returnValue(true));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($mockReflectionService));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->never())->method('getBaseValidatorConjunction');

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionReturnsNullIfNoValidatorBuilt()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->at(0))->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(1))->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will($this->returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $this->assertNull($validatorResolver->_call('buildBaseValidatorConjunction', 'NonExistingClassName', 'NonExistingClassName', ['Default']));
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction()
    {
        $mockObject = $this->createMock(\stdClass::class);
        $className = get_class($mockObject);

        $propertyTagsValues = [
            'foo' => [
                'var' => ['string'],
            ],
            'bar' => [
                'var' => ['integer'],
            ],
            'baz' => [
                'var' => ['array<TYPO3\TestPackage\Quux>']
            ]
        ];
        $validateAnnotations = [
            'foo' => [
                new Annotations\Validate([
                    'type' => 'Foo',
                    'options' => ['bar' => 'baz'],
                ]),
                new Annotations\Validate([
                    'type' => 'Bar',
                ]),
                new Annotations\Validate([
                    'type' => 'Baz',
                ]),
            ],
            'bar' => [
                new Annotations\Validate([
                    'type' => 'TYPO3\TestPackage\Quux',
                ]),
            ],
        ];

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(0))->method('getClassSchema')->will($this->returnValue(null));
        $mockReflectionService->expects($this->at(1))->method('getClassPropertyNames')->with($className)->will($this->returnValue(['foo', 'bar', 'baz']));
        $mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
        $mockReflectionService->expects($this->at(3))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(4))->method('getPropertyAnnotations')->with(get_class($mockObject), 'foo', Annotations\Validate::class)->will($this->returnValue($validateAnnotations['foo']));
        $mockReflectionService->expects($this->at(5))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));
        $mockReflectionService->expects($this->at(6))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(7))->method('getPropertyAnnotations')->with(get_class($mockObject), 'bar', Annotations\Validate::class)->will($this->returnValue($validateAnnotations['bar']));
        $mockReflectionService->expects($this->at(8))->method('getPropertyTagsValues')->with($className, 'baz')->will($this->returnValue($propertyTagsValues['baz']));
        $mockReflectionService->expects($this->at(9))->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(10))->method('getPropertyAnnotations')->with(get_class($mockObject), 'baz', Annotations\Validate::class)->will($this->returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($mockReflectionService));

        $mockObjectValidator = $this->createMock(GenericObjectValidator::class);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        $validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(4))->method('createValidator')->with(CollectionValidator::class, ['elementType' => 'TYPO3\TestPackage\Quux', 'validationGroups' => ['Default']])->will($this->returnValue($mockObjectValidator));

        $validatorResolver->_call('buildBaseValidatorConjunction', $className . 'Default', $className, ['Default']);
        $builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
        $this->assertInstanceOf(ConjunctionValidator::class, $builtValidators[$className . 'Default']);
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

        $fooPropertyTagsValues = [
            'bar' => [
                'var' => [$barClassName],
            ]
        ];
        $barPropertyTagsValues = [
            'foo' => [
                'var' => [$fooClassName],
            ]
        ];

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will($this->returnValue([]));
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValueMap([
            [$fooClassName, ['bar']],
            [$barClassName, ['foo']]
        ]));
        $mockReflectionService->expects($this->any())->method('getPropertyTagsValues')->will($this->returnValueMap([
            [$fooClassName, 'bar', $fooPropertyTagsValues['bar']],
            [$barClassName, 'foo', $barPropertyTagsValues['foo']]
        ]));
        $mockReflectionService->expects($this->any())->method('isPropertyAnnotatedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->any())->method('getPropertyAnnotations')->will($this->returnValue([]));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(true));
        $mockObjectManager->expects($this->any())->method('getScope')->will($this->returnValue(Configuration::SCOPE_PROTOTYPE));
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($mockReflectionService));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        /* @var $validatorChain ConjunctionValidator */
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
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
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
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $this->assertEquals('Raw', $validatorResolver->_call('getValidatorType', 'mixed'));
    }

    /**
     * @test
     */
    public function resetEmptiesBaseValidatorConjunctions()
    {
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
        $mockConjunctionValidator = $this->createMock(ConjunctionValidator::class);
        $validatorResolver->_set('baseValidatorConjunctions', ['SomeId##' => $mockConjunctionValidator]);

        $validatorResolver->reset();
        $this->assertEmpty($validatorResolver->_get('baseValidatorConjunctions'));
    }
}
