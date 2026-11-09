<?php

declare(strict_types=1);

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
use Neos\Flow\Annotations\IgnoreValidation;
use Neos\Flow\Annotations\Validate;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Exception\InvalidValidationConfigurationException;
use Neos\Flow\Validation\Validator\CollectionValidator;
use Neos\Flow\Validation\Validator\ConjunctionValidator;
use Neos\Flow\Validation\Validator\DateTimeValidator;
use Neos\Flow\Validation\Validator\GenericObjectValidator;
use Neos\Flow\Validation\Validator\IntegerValidator;
use Neos\Flow\Validation\Validator\PolyTypeObjectValidatorInterface;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Flow\Validation\ValidatorResolver;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the validator resolver
 *
 */
final class ValidatorResolverTest extends UnitTestCase
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

        $this->validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, []);
        $this->inject($this->validatorResolver, 'objectManager', $this->mockObjectManager);
    }

    #[Test]
    public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved()
    {
        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn(['Foo']);

        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $matcher = self::atLeast(2);
        $this->mockObjectManager->expects($matcher)->method('isRegistered')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('Foo', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('Neos\Flow\Validation\Validator\FooValidator', $parameters[0]);
            }
            return false;
        });

        self::assertFalse($this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    #[Test]
    public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegisteredAndImplementsValidatorInterface()
    {
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $this->mockObjectManager->method('isRegistered')->with('Foo')->willReturn(true);
        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn(['Foo']);

        self::assertSame('Foo', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    #[Test]
    public function resolveValidatorObjectNameReturnsFalseIfAnObjectOfTheArgumentNameIsRegisteredButDoesNotImplementValidatorInterface()
    {
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $matcher = self::atLeast(2);
        $this->mockObjectManager->expects($matcher)->method('isRegistered')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('Foo', $parameters[0]);
                return false;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('Neos\Flow\Validation\Validator\FooValidator', $parameters[0]);
                return true;
            }
        });
        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn(['Bar']);

        self::assertFalse($this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    #[Test]
    public function resolveValidatorObjectNameReturnsValidatorObjectNameIfAnObjectOfTheArgumentNameIsRegisteredAndDoesNotImplementValidatorInterfaceAndAValidatorForTheObjectExists()
    {
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $matcher = self::atLeast(2);
        $this->mockObjectManager->expects($matcher)->method('isRegistered')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('DateTime', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame(DateTimeValidator::class, $parameters[0]);
            }
            return true;
        });
        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn([DateTimeValidator::class]);

        self::assertSame(DateTimeValidator::class, $this->validatorResolver->_call('resolveValidatorObjectName', 'DateTime'));
    }

    #[Test]
    public function resolveValidatorObjectNameRemovesALeadingBackslashFromThePassedType()
    {
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $this->mockObjectManager->method('isRegistered')->with('Foo\Bar')->willReturn(true);
        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn(['Foo\Bar']);

        self::assertSame('Foo\Bar', $this->validatorResolver->_call('resolveValidatorObjectName', '\Foo\Bar'));
    }

    #[Test]
    public function resolveValidatorObjectNameCanResolveShorthandValidatornames()
    {
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $matcher = self::atLeast(2);
        $this->mockObjectManager->expects($matcher)->method('isRegistered')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('Mypkg:My', $parameters[0]);
                return false;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('Mypkg\Validation\Validator\MyValidator', $parameters[0]);
                return true;
            }
        });

        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn(['Mypkg\Validation\Validator\MyValidator']);

        self::assertSame('Mypkg\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg:My'));
    }

    #[Test]
    public function resolveValidatorObjectNameCanResolveShorthandValidatornamesForHierarchicalPackages()
    {
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $matcher = self::atLeast(2);
        $this->mockObjectManager->expects($matcher)->method('isRegistered')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('Mypkg.Foo:My', $parameters[0]);
                return false;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('Mypkg\Foo\Validation\Validator\MyValidator', $parameters[0]);
                return true;
            }
        });

        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn(['Mypkg\Foo\Validation\Validator\MyValidator']);

        self::assertSame('Mypkg\Foo\Validation\Validator\MyValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Mypkg.Foo:My'));
    }

    #[Test]
    public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators()
    {
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $matcher = self::atLeast(2);
        $this->mockObjectManager->expects($matcher)->method('isRegistered')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('Foo', $parameters[0]);
                return false;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('Neos\Flow\Validation\Validator\FooValidator', $parameters[0]);
                return true;
            }
        });
        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn(['Neos\Flow\Validation\Validator\FooValidator']);
        self::assertSame('Neos\Flow\Validation\Validator\FooValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    #[Test]
    public function resolveValidatorObjectNameCallsGetValidatorType()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);

        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ValidatorInterface::class)->willReturn([]);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['getValidatorType']);
        $validatorResolver->_set('objectManager', $mockObjectManager);

        $validatorResolver->expects($this->once())->method('getValidatorType')->with('someDataType');
        $validatorResolver->_call('resolveValidatorObjectName', 'someDataType');
    }

    #[Test]
    public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions()
    {
        $className = 'Test' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $className . ' implements \Neos\Flow\Validation\Validator\ValidatorInterface {
				protected $options = array();
				public function __construct(array $options = array()) {
					$this->options = $options;
				}
				public function validate($subject) {}
				public function getOptions() { return $this->options; }
			}');
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getScope')->with($className)->willReturn(Configuration::SCOPE_PROTOTYPE);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->willReturn($className);
        $validator = $validatorResolver->createValidator($className, ['foo' => 'bar']);
        self::assertInstanceOf($className, $validator);
        self::assertEquals(['foo' => 'bar'], $validator->getOptions());
    }

    #[Test]
    public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved()
    {
        $validatorResolver = $this->getMockBuilder(ValidatorResolver::class)->onlyMethods(['resolveValidatorObjectName'])->getMock();
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->willReturn(false);
        $validator = $validatorResolver->createValidator('Foo', ['foo' => 'bar']);
        self::assertNull($validator);
    }

    #[Test]
    public function createValidatorThrowsExceptionForSingletonValidatorsWithOptions()
    {
        $this->expectException(InvalidValidationConfigurationException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getScope')->with('FooType')->willReturn(Configuration::SCOPE_SINGLETON);

        $validatorResolver = $this->getMockBuilder(ValidatorResolver::class)->onlyMethods(['resolveValidatorObjectName'])->getMock();
        $this->inject($validatorResolver, 'objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('FooType')->willReturn('FooType');
        $validatorResolver->createValidator('FooType', ['foo' => 'bar']);
    }

    #[Test]
    public function buildBaseValidatorCachesTheResultOfTheBuildBaseValidatorConjunctionCalls()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $matcher = self::exactly(2);
        $mockReflectionService->expects($matcher)->method('getAllImplementationClassNamesForInterface')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(ValidatorInterface::class, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame(PolyTypeObjectValidatorInterface::class, $parameters[0]);
            }
            return [];
        });
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->willReturn($mockReflectionService);
        $this->validatorResolver->_set('objectManager', $mockObjectManager);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result1 = $this->validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
        self::assertInstanceOf(ConjunctionValidator::class, $result1, '#1');

        $result2 = $this->validatorResolver->getBaseValidatorConjunction('TYPO3\Virtual\Foo');
        self::assertSame($result1, $result2, '#2');
    }

    #[Test]
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments()
    {
        $mockController = $this->getAccessibleMock(ActionController::class, [], [], '', false);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->willReturn([]);

        $this->validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $this->validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
        self::assertSame([], $result);
    }

    #[Test]
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
            new Validate(
                '$arg1',
                'Foo',
                ['bar' => 'baz']
            ),
            new Validate(
                '$arg1',
                'Bar'
            ),
            new Validate(
                '$arg2',
                'Neos\TestPackage\Quux'
            ),
        ];

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->willReturn($methodParameters);
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Validate::class)->willReturn($validateAnnotations);

        $mockStringValidator = $this->createStub(ValidatorInterface::class);
        $mockArrayValidator = $this->createStub(ValidatorInterface::class);
        $mockFooValidator = $this->createStub(ValidatorInterface::class);
        $mockBarValidator = $this->createStub(ValidatorInterface::class);
        $mockQuuxValidator = $this->createStub(ValidatorInterface::class);

        $conjunction1 = $this->createMock(ConjunctionValidator::class);
        $matcher = self::exactly(3);
        $conjunction1->expects($matcher)->method('addValidator')->willReturnCallback(function (...$parameters) use ($matcher, $mockStringValidator, $mockFooValidator, $mockBarValidator) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($mockStringValidator, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($mockFooValidator, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame($mockBarValidator, $parameters[0]);
            }
        });

        $conjunction2 = $this->createMock(ConjunctionValidator::class);
        $matcher = self::exactly(2);
        $conjunction2->expects($matcher)->method('addValidator')->willReturnCallback(function (...$parameters) use ($matcher, $mockArrayValidator, $mockQuuxValidator) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($mockArrayValidator, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($mockQuuxValidator, $parameters[0]);
            }
        });

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $matcher = self::exactly(7);
        $validatorResolver->expects($matcher)->method('createValidator')->willReturnCallback(function (...$parameters) use ($matcher, $conjunction1, $conjunction2, $mockFooValidator, $mockBarValidator, $mockStringValidator, $mockArrayValidator, $mockQuuxValidator) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(ConjunctionValidator::class, $parameters[0]);
                return $conjunction1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('string', $parameters[0]);
                return $mockStringValidator;
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame(ConjunctionValidator::class, $parameters[0]);
                return $conjunction2;
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('array', $parameters[0]);
                return $mockArrayValidator;
            }
            if ($matcher->numberOfInvocations() === 5) {
                $this->assertSame('Foo', $parameters[0]);
                $this->assertSame(['bar' => 'baz'], $parameters[1]);
                return $mockFooValidator;
            }
            if ($matcher->numberOfInvocations() === 6) {
                $this->assertSame('Bar', $parameters[0]);
                return $mockBarValidator;
            }
            if ($matcher->numberOfInvocations() === 7) {
                $this->assertSame('Neos\TestPackage\Quux', $parameters[0]);
                return $mockQuuxValidator;
            }
        });

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
        self::assertEquals(['arg1' => $conjunction1, 'arg2' => $conjunction2], $result);
    }

    #[Test]
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyConjunctionIfNoValidatorIsFoundForMethodParameter()
    {
        $mockObject = new \stdClass();

        $methodParameters = [
            'arg' => [
                'type' => 'FLOW8\Blog\Domain\Model\Blog'
            ]
        ];

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->willReturn($methodParameters);
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Validate::class)->willReturn([]);

        $conjunction = $this->createMock(ConjunctionValidator::class);
        $conjunction->expects($this->never())->method('addValidator');

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $validatorResolver->expects($this->once())->method('createValidator')->with(ConjunctionValidator::class)->willReturn($conjunction);

        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }

    #[Test]
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
            new Validate(
                '$arg2',
                'Neos\TestPackage\Quux'
            ),
        ];

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getMethodAnnotations')->with(get_class($mockObject), 'fooAction', Validate::class)->willReturn($validateAnnotations);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->willReturn($methodParameters);

        $mockStringValidator = $this->createStub(ValidatorInterface::class);
        $mockQuuxValidator = $this->createStub(ValidatorInterface::class);
        $conjunction1 = $this->createMock(ConjunctionValidator::class);
        $conjunction1->expects($this->once())->method('addValidator')->with($mockStringValidator);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator'], [], '', false);
        $matcher = self::exactly(3);
        $validatorResolver->expects($matcher)->method('createValidator')->willReturnCallback(function (...$parameters) use ($matcher, $conjunction1, $mockStringValidator, $mockQuuxValidator) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(ConjunctionValidator::class, $parameters[0]);
                return $conjunction1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('string', $parameters[0]);
                return $mockStringValidator;
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('Neos\TestPackage\Quux', $parameters[0]);
                return $mockQuuxValidator;
            }
        });
        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }

    #[Test]
    public function buildBaseValidatorConjunctionAddsCustomValidatorToTheReturnedConjunction()
    {
        $modelClassName = 'Page' . md5(uniqid((string)mt_rand(), true));
        $validatorClassName = 'Domain\Validator\Content\\' . $modelClassName . 'Validator';
        eval('namespace Domain\Model\Content; class ' . $modelClassName . '{}');

        $modelClassName = 'Domain\Model\Content\\' . $modelClassName;

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('getClassPropertyNames')->willReturn([]);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($mockReflectionService);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('createValidator')->with($validatorClassName)->willReturn(new IntegerValidator());
        $mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->willReturn([]);

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
        $builtValidators = $validatorResolver->_get('baseValidatorConjunctions');

        self::assertFalse($builtValidators[$modelClassName]->validate(10)->hasErrors());
        self::assertTrue($builtValidators[$modelClassName]->validate('foo')->hasErrors());
    }

    #[Test]
    public function addCustomValidatorsAddsExpectedPolyTypeValidatorToTheConjunction()
    {
        $highPriorityValidatorClassName = 'RandomHighPrio' . md5(uniqid((string)mt_rand(), true)) . 'PolyTypeValidator';
        $lowPriorityValidatorClassName = 'RandomLowPrio' . md5(uniqid((string)mt_rand(), true)) . 'PolyTypeValidator';
        $modelClassName = 'Acme\Test\Content\Page' . md5(uniqid((string)mt_rand(), true));

        $mockLowPriorityValidator = $this->createMock(PolyTypeObjectValidatorInterface::class, [], [], $lowPriorityValidatorClassName);
        $mockLowPriorityValidator->expects($this->atLeastOnce())->method('canValidate')->with($modelClassName)->willReturn(true);
        $mockLowPriorityValidator->expects($this->atLeastOnce())->method('getPriority')->willReturn(100);
        $mockHighPriorityValidator = $this->createMock(PolyTypeObjectValidatorInterface::class, [], [], $highPriorityValidatorClassName);
        $mockHighPriorityValidator->expects($this->atLeastOnce())->method('canValidate')->with($modelClassName)->willReturn(true);
        $mockHighPriorityValidator->expects($this->atLeastOnce())->method('getPriority')->willReturn(200);

        $mockConjunctionValidator = $this->getMockBuilder(ConjunctionValidator::class)->onlyMethods(['addValidator'])->getMock();
        $mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockHighPriorityValidator);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->willReturn([$highPriorityValidatorClassName, $lowPriorityValidatorClassName]);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($mockReflectionService);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $matcher = self::exactly(3);
        $validatorResolver->expects($matcher)->method('createValidator')->willReturnCallback(function (...$parameters) use ($matcher, $modelClassName, $highPriorityValidatorClassName, $lowPriorityValidatorClassName, $mockHighPriorityValidator, $mockLowPriorityValidator) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($modelClassName . 'Validator', $parameters[0]);
                return null;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($highPriorityValidatorClassName, $parameters[0]);
                return $mockHighPriorityValidator;
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame($lowPriorityValidatorClassName, $parameters[0]);
                return $mockLowPriorityValidator;
            }
        });

        $validatorResolver->_callRef('addCustomValidators', $modelClassName, $mockConjunctionValidator);
    }

    #[Test]
    public function buildBaseValidatorConjunctionAddsValidatorsOnlyForPropertiesHoldingPrototypes()
    {
        $entityClassName = 'Entity' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $entityClassName . '{}');
        $otherClassName = 'Other' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $otherClassName . '{}');
        $modelClassName = 'Model' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $modelClassName . '{}');

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('isRegistered')->willReturn(true);
        $matcher = self::exactly(2);
        $mockObjectManager->expects($matcher)->method('getScope')->willReturnCallback(function (...$parameters) use ($matcher, $entityClassName, $otherClassName) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($entityClassName, $parameters[0]);
                return Configuration::SCOPE_PROTOTYPE;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($otherClassName, $parameters[0]);
                return null;
            }
        });

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->willReturn([]);
        $mockReflectionService->method('getClassPropertyNames')->willReturn(['entityProperty', 'otherProperty']);
        $matcher = self::exactly(2);
        $mockReflectionService->expects($matcher)->method('getPropertyTagsValues')->willReturnCallback(function (...$parameters) use ($matcher, $modelClassName, $entityClassName, $otherClassName) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($modelClassName, $parameters[0]);
                $this->assertSame('entityProperty', $parameters[1]);
                return ['var' => [$entityClassName]];
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($modelClassName, $parameters[0]);
                $this->assertSame('otherProperty', $parameters[1]);
                return ['var' => [$otherClassName]];
            }
        });
        $mockReflectionService->expects($this->exactly(2))->method('isPropertyAnnotatedWith')->willReturn(false);
        $matcher = self::exactly(2);
        $mockReflectionService->expects($matcher)->method('getPropertyAnnotations')->willReturnCallback(function (...$parameters) use ($matcher, $modelClassName) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($modelClassName, $parameters[0]);
                $this->assertSame('entityProperty', $parameters[1]);
                $this->assertSame(Validate::class, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($modelClassName, $parameters[0]);
                $this->assertSame('otherProperty', $parameters[1]);
                $this->assertSame(Validate::class, $parameters[2]);
            }
            return [];
        });

        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($mockReflectionService);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->willReturn($this->createMock(ConjunctionValidator::class));

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
    }

    #[Test]
    public function buildBaseValidatorConjunctionSkipsPropertiesAnnotatedWithIgnoreValidation()
    {
        $modelClassName = 'Model' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $modelClassName . '{}');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('getAllImplementationClassNamesForInterface')->willReturn([]);
        $mockReflectionService->expects($this->once())->method('getClassPropertyNames')->willReturn(['entityProperty']);
        $mockReflectionService->expects($this->once())->method('getPropertyTagsValues')->with($modelClassName, 'entityProperty')->willReturn(['var' => ['ToBeIgnored']]);
        $mockReflectionService->expects($this->once())->method('isPropertyAnnotatedWith')->with($modelClassName, 'entityProperty', IgnoreValidation::class)->willReturn(true);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($mockReflectionService);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator', 'getBaseValidatorConjunction']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->never())->method('getBaseValidatorConjunction');

        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName, ['Default']);
    }

    #[Test]
    public function buildBaseValidatorConjunctionReturnsNullIfNoValidatorBuilt()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $matcher = self::exactly(2);
        $mockReflectionService->expects($matcher)->method('getAllImplementationClassNamesForInterface')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(ValidatorInterface::class, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame(PolyTypeObjectValidatorInterface::class, $parameters[0]);
            }
            return [];
        });
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->willReturn($mockReflectionService);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, []);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->_set('reflectionService', $mockReflectionService);

        self::assertNull($validatorResolver->_call('buildBaseValidatorConjunction', 'NonExistingClassName', 'NonExistingClassName', ['Default']));
    }

    #[Test]
    public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction()
    {
        $mockObject = $this->createStub(\stdClass::class);
        $className = get_class($mockObject);

        $propertyTagsValues = [
            'foo' => [
                'var' => ['string'],
            ],
            'bar' => [
                'var' => ['integer'],
            ],
            'baz' => [
                'var' => ['array<Neos\TestPackage\Quux>']
            ]
        ];
        $validateAnnotations = [
            'foo' => [
                new Validate(
                    null,
                    'Foo',
                    ['bar' => 'baz']
                ),
                new Validate(
                    null,
                    'Bar'
                ),
                new Validate(
                    null,
                    'Baz'
                ),
            ],
            'bar' => [
                new Validate(
                    null,
                    'Neos\TestPackage\Quux'
                ),
            ],
        ];

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->willReturn([]);
        $mockReflectionService->method('getClassSchema')->willReturn(null);
        $mockReflectionService->method('getClassPropertyNames')->with($className)->willReturn(['foo', 'bar', 'baz']);
        $matcher = self::exactly(3);
        $mockReflectionService->expects($matcher)->method('getPropertyTagsValues')->willReturnCallback(function (...$parameters) use ($matcher, $className, $propertyTagsValues) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($className, $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
                return $propertyTagsValues['bar'];
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($className, $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
                return $propertyTagsValues['foo'];
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame($className, $parameters[0]);
                $this->assertSame('baz', $parameters[1]);
                return $propertyTagsValues['baz'];
            }
        });
        $mockReflectionService->expects($this->exactly(3))->method('isPropertyAnnotatedWith')->willReturn(false);
        $matcher = self::exactly(3);
        $mockReflectionService->expects($matcher)->method('getPropertyAnnotations')->willReturnCallback(function (...$parameters) use ($matcher, $mockObject, $validateAnnotations) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(get_class($mockObject), $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
                $this->assertSame(Validate::class, $parameters[2]);
                return $validateAnnotations['foo'];
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame(get_class($mockObject), $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
                $this->assertSame(Validate::class, $parameters[2]);
                return $validateAnnotations['bar'];
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame(get_class($mockObject), $parameters[0]);
                $this->assertSame('baz', $parameters[1]);
                $this->assertSame(Validate::class, $parameters[2]);
                return [];
            }
        });
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($mockReflectionService);

        $mockObjectValidator = $this->createStub(GenericObjectValidator::class);

        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $matcher = self::exactly(6);

        $validatorResolver->expects($matcher)->method('createValidator')->willReturnCallback(function (...$parameters) use ($matcher, $className, $mockObjectValidator) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('Foo', $parameters[0]);
                $this->assertSame(['bar' => 'baz'], $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('Bar', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('Baz', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('Neos\TestPackage\Quux', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 5) {
                $this->assertSame(CollectionValidator::class, $parameters[0]);
                $this->assertSame(['elementType' => 'Neos\TestPackage\Quux', 'validationGroups' => ['Default']], $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 6) {
                $this->assertSame($className . 'Validator', $parameters[0]);
            }
            return $mockObjectValidator;
        });

        $validatorResolver->_call('buildBaseValidatorConjunction', $className . 'Default', $className, ['Default']);
        $builtValidators = $validatorResolver->_get('baseValidatorConjunctions');
        self::assertInstanceOf(ConjunctionValidator::class, $builtValidators[$className . 'Default']);
    }

    #[Test]
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

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(PolyTypeObjectValidatorInterface::class)->willReturn([]);
        $mockReflectionService->expects($this->exactly(2))->method('getClassPropertyNames')->willReturnMap([
            [$fooClassName, ['bar']],
            [$barClassName, ['foo']]
        ]);
        $mockReflectionService->expects($this->exactly(2))->method('getPropertyTagsValues')->willReturnMap([
            [$fooClassName, 'bar', $fooPropertyTagsValues['bar']],
            [$barClassName, 'foo', $barPropertyTagsValues['foo']]
        ]);
        $mockReflectionService->method('isPropertyAnnotatedWith')->willReturn(false);
        $mockReflectionService->method('getPropertyAnnotations')->willReturn([]);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('isRegistered')->willReturn(true);
        $mockObjectManager->method('getScope')->willReturn(Configuration::SCOPE_PROTOTYPE);
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($mockReflectionService);

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

    #[Test]
    public function getValidatorTypeCorrectlyRenamesPhpDataTypes()
    {
        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, []);
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

    #[Test]
    public function getValidatorTypeRenamesMixedToRaw()
    {
        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, []);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        self::assertEquals('Raw', $validatorResolver->_call('getValidatorType', 'mixed'));
    }

    #[Test]
    public function resetEmptiesBaseValidatorConjunctions()
    {
        $validatorResolver = $this->getAccessibleMock(ValidatorResolver::class, []);
        $mockConjunctionValidator = $this->createStub(ConjunctionValidator::class);
        $validatorResolver->_set('baseValidatorConjunctions', ['SomeId##' => $mockConjunctionValidator]);

        $validatorResolver->reset();
        self::assertEmpty($validatorResolver->_get('baseValidatorConjunctions'));
    }
}
