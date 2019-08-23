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
use Neos\Flow\Validation\Exception\InvalidValidationConfigurationException;
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

    protected function setUp(): void
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
        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue(['Foo']));

        $this->mockObjectManager->expects(self::at(0))->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::at(1))->method('isRegistered')->with('Foo')->will(self::returnValue(false));
        $this->mockObjectManager->expects(self::at(2))->method('isRegistered')->with('Neos\Flow\Validation\Validator\FooValidator')->will(self::returnValue(false));

        self::assertSame(false, $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegisteredAndImplementsValidatorInterface()
    {
        $this->mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::any())->method('isRegistered')->with('Foo')->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue(['Foo']));

        self::assertSame('Foo', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsFalseIfAnObjectOfTheArgumentNameIsRegisteredButDoesNotImplementValidatorInterface()
    {
        $this->mockObjectManager->expects(self::at(0))->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::at(1))->method('isRegistered')->with('Foo')->will(self::returnValue(true));
        $this->mockObjectManager->expects(self::at(2))->method('isRegistered')->with('Neos\Flow\Validation\Validator\FooValidator')->will(self::returnValue(false));
        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue(['Bar']));

        self::assertFalse($this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsValidatorObjectNameIfAnObjectOfTheArgumentNameIsRegisteredAndDoesNotImplementValidatorInterfaceAndAValidatorForTheObjectExists()
    {
        $this->mockObjectManager->expects(self::at(0))->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::at(1))->method('isRegistered')->with('DateTime')->will(self::returnValue(true));
        $this->mockObjectManager->expects(self::at(2))->method('isRegistered')->with(DateTimeValidator::class)->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue([DateTimeValidator::class]));

        self::assertSame(DateTimeValidator::class, $this->validatorResolver->_call('resolveValidatorObjectName', 'DateTime'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameRemovesALeadingBackslashFromThePassedType()
    {
        $this->mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::any())->method('isRegistered')->with('Foo\Bar')->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue(['Foo\Bar']));

        self::assertSame('Foo\Bar', $this->validatorResolver->_call('resolveValidatorObjectName', '\Foo\Bar'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShorthandValidatornames()
    {
        $this->mockObjectManager->expects(self::at(0))->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::at(1))->method('isRegistered')->with('Mypkg:My')->will(self::returnValue(false));
        $this->mockObjectManager->expects(self::at(2))->method('isRegistered')->with('Mypkg\Validation\Validator\MyValidator')->will(self::returnValue(true));

        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue(['Mypkg\Validation\Validator\MyValidator']));

        self::assertSame('Mypkg\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg:My'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShorthandValidatornamesForHierarchicalPackages()
    {
        $this->mockObjectManager->expects(self::at(0))->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::at(1))->method('isRegistered')->with('Mypkg.Foo:My')->will(self::returnValue(false));
        $this->mockObjectManager->expects(self::at(2))->method('isRegistered')->with('Mypkg\Foo\Validation\Validator\\MyValidator')->will(self::returnValue(true));

        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue(['Mypkg\Foo\Validation\Validator\MyValidator']));

        self::assertSame('Mypkg\Foo\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg.Foo:My'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators()
    {
        $this->mockObjectManager->expects(self::at(0))->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));
        $this->mockObjectManager->expects(self::at(1))->method('isRegistered')->with('Foo')->will(self::returnValue(false));
        $this->mockObjectManager->expects(self::at(2))->method('isRegistered')->with('Neos\Flow\Validation\Validator\FooValidator')->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue(['Neos\Flow\Validation\Validator\FooValidator']));
        self::assertSame('Neos\Flow\Validation\Validator\FooValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCallsGetValidatorType()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($this->mockReflectionService));

        $this->mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue([]));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['getValidatorType']);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        $validatorResolver->expects(self::once())->method('getValidatorType')->with('someDataType');
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
        $mockObjectManager->expects(self::any())->method('getScope')->with($className)->will(self::returnValue(Configuration::SCOPE_PROTOTYPE));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects(self::once())->method('resolveValidatorObjectName')->with($className)->will(self::returnValue($className));
        $validator = $validatorResolver->createValidator($className, ['foo' => 'bar']);
        self::assertInstanceOf($className, $validator);
        self::assertEquals(['foo' => 'bar'], $validator->getOptions());
    }

    /**
     * @test
     */
    public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved()
    {
        $validatorResolver = $this->getMockBuilder(ValidatorResolver::class)->setMethods(['resolveValidatorObjectName'])->getMock();
        $validatorResolver->expects(self::once())->method('resolveValidatorObjectName')->with('Foo')->will(self::returnValue(false));
        $validator = $validatorResolver->createValidator('Foo', ['foo' => 'bar']);
        self::assertNull($validator);
    }

    /**
     * @test
     */
    public function createValidatorThrowsExceptionForSingletonValidatorsWithOptions()
    {
        $this->expectException(InvalidValidationConfigurationException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('getScope')->with('FooType')->will(self::returnValue(Configuration::SCOPE_SINGLETON));

        $validatorResolver = $this->getMockBuilder(ValidatorResolver::class)->setMethods(['resolveValidatorObjectName'])->getMock();
        $this->inject($validatorResolver, 'objectManager', $mockObjectManager);
        $validatorResolver->expects(self::once())->method('resolveValidatorObjectName')->with('FooType')->will(self::returnValue('FooType'));
        $validatorResolver->createValidator('FooType', ['foo' => 'bar']);
    }

    /**
     * @test
     */
    public function buildBaseValidatorCachesTheResultOfTheBuildBaseValidatorConjunctionCalls()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::at(0))->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue([]));
        $mockReflectionService->expects(self::at(1))->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will(self::returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->will(self::returnValue($mockReflectionService));
        $this->validatorResolver->_set('objectManager', $mockObjectManager);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result1 = $this->validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
        self::assertInstanceOf(ConjunctionValidator::class, $result1, '#1');

        $result2 = $this->validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
        self::assertSame($result1, $result2, '#2');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments()
    {
        $mockController = $this->getAccessibleMock(ActionController::class, ['fooAction'], [], '', false);

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects(self::once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will(self::returnValue([]));

        $this->validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
        self::assertSame([], $result);
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
        $mockReflectionService->expects(self::once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will(self::returnValue($methodParameters));
        $mockReflectionService->expects(self::once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Annotations\Validate::class)->will(self::returnValue($validateAnnotations));

        $mockStringValidator = $this->createMock(ValidatorInterface::class);
        $mockArrayValidator = $this->createMock(ValidatorInterface::class);
        $mockFooValidator = $this->createMock(ValidatorInterface::class);
        $mockBarValidator = $this->createMock(ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(ValidatorInterface::class);

        $conjunction1 = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction1->expects(self::at(0))->method('addValidator')->with($mockStringValidator);
        $conjunction1->expects(self::at(1))->method('addValidator')->with($mockFooValidator);
        $conjunction1->expects(self::at(2))->method('addValidator')->with($mockBarValidator);

        $conjunction2 = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction2->expects(self::at(0))->method('addValidator')->with($mockArrayValidator);
        $conjunction2->expects(self::at(1))->method('addValidator')->with($mockQuuxValidator);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $validatorResolver->expects(self::at(0))->method('createValidator')->with(ConjunctionValidator::class)->will(self::returnValue($conjunction1));
        $validatorResolver->expects(self::at(1))->method('createValidator')->with('string')->will(self::returnValue($mockStringValidator));
        $validatorResolver->expects(self::at(2))->method('createValidator')->with(ConjunctionValidator::class)->will(self::returnValue($conjunction2));
        $validatorResolver->expects(self::at(3))->method('createValidator')->with('array')->will(self::returnValue($mockArrayValidator));
        $validatorResolver->expects(self::at(4))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will(self::returnValue($mockFooValidator));
        $validatorResolver->expects(self::at(5))->method('createValidator')->with('Bar')->will(self::returnValue($mockBarValidator));
        $validatorResolver->expects(self::at(6))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will(self::returnValue($mockQuuxValidator));

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
        self::assertEquals(['arg1' => $conjunction1, 'arg2' => $conjunction2], $result);
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
        $mockReflectionService->expects(self::once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will(self::returnValue($methodParameters));
        $mockReflectionService->expects(self::once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Annotations\Validate::class)->will(self::returnValue([]));

        $conjunction = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction->expects(self::never())->method('addValidator');

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $validatorResolver->expects(self::at(0))->method('createValidator')->with(ConjunctionValidator::class)->will(self::returnValue($conjunction));

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists()
    {
        $this->expectException(InvalidValidationConfigurationException::class);
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
        $mockReflectionService->expects(self::once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Annotations\Validate::class)->will(self::returnValue($validateAnnotations));
        $mockReflectionService->expects(self::once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will(self::returnValue($methodParameters));

        $mockStringValidator = $this->createMock(ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(ValidatorInterface::class);
        $conjunction1 = $this->getMockBuilder(ConjunctionValidator::class)->disableOriginalConstructor()->getMock();
        $conjunction1->expects(self::at(0))->method('addValidator')->with($mockStringValidator);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $validatorResolver->expects(self::at(0))->method('createValidator')->with(ConjunctionValidator::class)->will(self::returnValue($conjunction1));
        $validatorResolver->expects(self::at(1))->method('createValidator')->with('string')->will(self::returnValue($mockStringValidator));
        $validatorResolver->expects(self::at(2))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will(self::returnValue($mockQuuxValidator));

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
        $mockReflectionService->expects(self::any())->method('getClassPropertyNames')->will(self::returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects(self::once())->method('createValidator')->with($validatorClassName)->will(self::returnValue(new EmailAddressValidator()));
        $mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will(self::returnValue([]));

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
        $builtValidators = $validatorResolver->_get('baseValidatorConjunctions');

        self::assertFalse($builtValidators[$modelClassName]->validate('foo@example.com')->hasErrors());
        self::assertTrue($builtValidators[$modelClassName]->validate('foo')->hasErrors());
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
        $mockLowPriorityValidator->expects(self::atLeastOnce())->method('canValidate')->with($modelClassName)->will(self::returnValue(true));
        $mockLowPriorityValidator->expects(self::atLeastOnce())->method('getPriority')->will(self::returnValue(100));
        $mockHighPriorityValidator = $this->createMock(PolyTypeObjectValidatorInterface::class, [], [], $highPriorityValidatorClassName);
        $mockHighPriorityValidator->expects(self::atLeastOnce())->method('canValidate')->with($modelClassName)->will(self::returnValue(true));
        $mockHighPriorityValidator->expects(self::atLeastOnce())->method('getPriority')->will(self::returnValue(200));

        $mockConjunctionValidator = $this->getMockBuilder(ConjunctionValidator::class)->setMethods(['addValidator'])->getMock();
        $mockConjunctionValidator->expects(self::once())->method('addValidator')->with($mockHighPriorityValidator);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will(self::returnValue([$highPriorityValidatorClassName, $lowPriorityValidatorClassName]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects(self::at(0))->method('createValidator')->will(self::returnValue(null));
        $validatorResolver->expects(self::at(1))->method('createValidator')->with($highPriorityValidatorClassName)->will(self::returnValue($mockHighPriorityValidator));
        $validatorResolver->expects(self::at(2))->method('createValidator')->with($lowPriorityValidatorClassName)->will(self::returnValue($mockLowPriorityValidator));

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
        $mockObjectManager->expects(self::any())->method('isRegistered')->will(self::returnValue(true));
        $mockObjectManager->expects(self::at(1))->method('getScope')->with($entityClassName)->will(self::returnValue(Configuration::SCOPE_PROTOTYPE));
        $mockObjectManager->expects(self::at(3))->method('getScope')->with($otherClassName)->will(self::returnValue(null));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will(self::returnValue([]));
        $mockReflectionService->expects(self::any())->method('getClassPropertyNames')->will(self::returnValue(['entityProperty', 'otherProperty']));
        $mockReflectionService->expects(self::at(1))->method('getClassSchema')->will(self::returnValue(null));
        $mockReflectionService->expects(self::at(2))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will(self::returnValue(['var' => [$entityClassName]]));
        $mockReflectionService->expects(self::at(3))->method('isPropertyAnnotatedWith')->will(self::returnValue(false));
        $mockReflectionService->expects(self::at(4))->method('getPropertyAnnotations')->with($modelClassName, 'entityProperty', Annotations\Validate::class)->will(self::returnValue([]));
        $mockReflectionService->expects(self::at(5))->method('getPropertyTagsValues')->with($modelClassName, 'otherProperty')->will(self::returnValue(['var' => [$otherClassName]]));
        $mockReflectionService->expects(self::at(6))->method('isPropertyAnnotatedWith')->will(self::returnValue(false));
        $mockReflectionService->expects(self::at(7))->method('getPropertyAnnotations')->with($modelClassName, 'otherProperty', Annotations\Validate::class)->will(self::returnValue([]));

        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->expects(self::once())->method('getBaseValidatorConjunction')->will(self::returnValue($this->getMockBuilder(ConjunctionValidator::class)->getMock()));

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
        $mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->will(self::returnValue([]));
        $mockReflectionService->expects(self::at(0))->method('getClassSchema')->will(self::returnValue(null));
        $mockReflectionService->expects(self::at(1))->method('getClassPropertyNames')->will(self::returnValue(['entityProperty']));
        $mockReflectionService->expects(self::at(2))->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->will(self::returnValue(['var' => ['ToBeIgnored']]));
        $mockReflectionService->expects(self::at(3))->method('isPropertyAnnotatedWith')->with($modelClassName, 'entityProperty', Annotations\IgnoreValidation::class)->will(self::returnValue(true));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($mockReflectionService));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects(self::never())->method('getBaseValidatorConjunction');

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionReturnsNullIfNoValidatorBuilt()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::at(0))->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->will(self::returnValue([]));
        $mockReflectionService->expects(self::at(1))->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will(self::returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->will(self::returnValue($mockReflectionService));
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);

        self::assertNull($validatorResolver->_call('buildBaseValidatorConjunction', 'NonExistingClassName', 'NonExistingClassName', ['Default']));
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
        $mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will(self::returnValue([]));
        $mockReflectionService->expects(self::at(0))->method('getClassSchema')->will(self::returnValue(null));
        $mockReflectionService->expects(self::at(1))->method('getClassPropertyNames')->with($className)->will(self::returnValue(['foo', 'bar', 'baz']));
        $mockReflectionService->expects(self::at(2))->method('getPropertyTagsValues')->with($className, 'foo')->will(self::returnValue($propertyTagsValues['foo']));
        $mockReflectionService->expects(self::at(3))->method('isPropertyAnnotatedWith')->will(self::returnValue(false));
        $mockReflectionService->expects(self::at(4))->method('getPropertyAnnotations')->with(get_class($mockObject), 'foo', Annotations\Validate::class)->will(self::returnValue($validateAnnotations['foo']));
        $mockReflectionService->expects(self::at(5))->method('getPropertyTagsValues')->with($className, 'bar')->will(self::returnValue($propertyTagsValues['bar']));
        $mockReflectionService->expects(self::at(6))->method('isPropertyAnnotatedWith')->will(self::returnValue(false));
        $mockReflectionService->expects(self::at(7))->method('getPropertyAnnotations')->with(get_class($mockObject), 'bar', Annotations\Validate::class)->will(self::returnValue($validateAnnotations['bar']));
        $mockReflectionService->expects(self::at(8))->method('getPropertyTagsValues')->with($className, 'baz')->will(self::returnValue($propertyTagsValues['baz']));
        $mockReflectionService->expects(self::at(9))->method('isPropertyAnnotatedWith')->will(self::returnValue(false));
        $mockReflectionService->expects(self::at(10))->method('getPropertyAnnotations')->with(get_class($mockObject), 'baz', Annotations\Validate::class)->will(self::returnValue([]));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($mockReflectionService));

        $mockObjectValidator = $this->createMock(GenericObjectValidator::class);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        $validatorResolver->expects(self::at(0))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will(self::returnValue($mockObjectValidator));
        $validatorResolver->expects(self::at(1))->method('createValidator')->with('Bar')->will(self::returnValue($mockObjectValidator));
        $validatorResolver->expects(self::at(2))->method('createValidator')->with('Baz')->will(self::returnValue($mockObjectValidator));
        $validatorResolver->expects(self::at(3))->method('createValidator')->with('TYPO3\TestPackage\Quux')->will(self::returnValue($mockObjectValidator));
        $validatorResolver->expects(self::at(4))->method('createValidator')->with(CollectionValidator::class, ['elementType' => 'TYPO3\TestPackage\Quux', 'validationGroups' => ['Default']])->will(self::returnValue($mockObjectValidator));

        $validatorResolver->_call('buildBaseValidatorConjunction', $className . 'Default', $className, ['Default']);
        $builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
        self::assertInstanceOf(ConjunctionValidator::class, $builtValidators[$className . 'Default']);
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
        $mockReflectionService->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->will(self::returnValue([]));
        $mockReflectionService->expects(self::any())->method('getClassPropertyNames')->will($this->returnValueMap([
            [$fooClassName, ['bar']],
            [$barClassName, ['foo']]
        ]));
        $mockReflectionService->expects(self::any())->method('getPropertyTagsValues')->will($this->returnValueMap([
            [$fooClassName, 'bar', $fooPropertyTagsValues['bar']],
            [$barClassName, 'foo', $barPropertyTagsValues['foo']]
        ]));
        $mockReflectionService->expects(self::any())->method('isPropertyAnnotatedWith')->will(self::returnValue(false));
        $mockReflectionService->expects(self::any())->method('getPropertyAnnotations')->will(self::returnValue([]));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('isRegistered')->will(self::returnValue(true));
        $mockObjectManager->expects(self::any())->method('getScope')->will(self::returnValue(Configuration::SCOPE_PROTOTYPE));
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($mockReflectionService));

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        /* @var $validatorChain ConjunctionValidator */
        $validatorChain = $validatorResolver->getBaseValidatorConjunction($fooClassName);
        $fooValidators = $validatorChain->getValidators();
        self::assertGreaterThan(0, $fooValidators->count());

        // ugh, it's so cumbersome to work with SplObjectStorage outside of iterations...
        $fooValidators->rewind();
        $barValidators = $fooValidators->current()->getPropertyValidators('bar');
        self::assertGreaterThan(0, $barValidators->count());

        $barValidators->rewind();
        $barValidators = $barValidators->current()->getValidators();
        self::assertGreaterThan(0, $barValidators->count());
        $barValidators->rewind();

        self::assertGreaterThan(0, $barValidators->current()->getPropertyValidators('foo')->count());
    }

    /**
     * @test
     */
    public function getValidatorTypeCorrectlyRenamesPhpDataTypes()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        self::assertEquals('Integer', $validatorResolver->_call('getValidatorType', 'integer'));
        self::assertEquals('Integer', $validatorResolver->_call('getValidatorType', 'int'));
        self::assertEquals('String', $validatorResolver->_call('getValidatorType', 'string'));
        self::assertEquals('Array', $validatorResolver->_call('getValidatorType', 'array'));
        self::assertEquals('Float', $validatorResolver->_call('getValidatorType', 'float'));
        self::assertEquals('Float', $validatorResolver->_call('getValidatorType', 'double'));
        self::assertEquals('Boolean', $validatorResolver->_call('getValidatorType', 'boolean'));
        self::assertEquals('Boolean', $validatorResolver->_call('getValidatorType', 'bool'));
        self::assertEquals('Number', $validatorResolver->_call('getValidatorType', 'number'));
        self::assertEquals('Number', $validatorResolver->_call('getValidatorType', 'numeric'));
    }

    /**
     * @test
     */
    public function getValidatorTypeRenamesMixedToRaw()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['dummy']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        self::assertEquals('Raw', $validatorResolver->_call('getValidatorType', 'mixed'));
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
        self::assertEmpty($validatorResolver->_get('baseValidatorConjunctions'));
    }
}
