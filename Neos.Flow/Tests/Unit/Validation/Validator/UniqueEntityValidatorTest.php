<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;
use Neos\Flow\Validation\Validator\UniqueEntityValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the unique entity validator
 */
final class UniqueEntityValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = UniqueEntityValidator::class;

    /**
     * @var MockObject
     * @see \Neos\Flow\Reflection\ClassSchema
     */
    protected $classSchema;

    /**
     * @var MockObject
     * @see \Neos\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->classSchema = $this->createMock(ClassSchema::class);

        $this->reflectionService = $this->createMock(ReflectionService::class);
        $this->reflectionService->method('getClassSchema')->willReturn(($this->classSchema));
        $this->inject($this->validator, 'reflectionService', $this->reflectionService);
    }

    #[Test]
    public function validatorThrowsExceptionIfValueIsNotAnObject()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358454270);
        $this->validator->validate('a string');
    }

    #[Test]
    public function validatorThrowsExceptionIfValueIsNotReflectedAtAll()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358454284);
        $this->classSchema->expects($this->once())->method('getModelType')->willReturn((null));

        $this->validator->validate(new \stdClass());
    }

    #[Test]
    public function validatorThrowsExceptionIfValueIsNotAFlowEntity()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358454284);
        $this->classSchema->expects($this->once())->method('getModelType')->willReturn((ClassSchema::MODELTYPE_VALUEOBJECT));

        $this->validator->validate(new \stdClass());
    }

    #[Test]
    public function validatorThrowsExceptionIfSetupPropertiesAreNotPresentInActualClass()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358960500);
        $this->prepareMockExpectations();
        $this->inject($this->validator, 'options', ['identityProperties' => ['propertyWhichDoesntExist']]);
        $this->classSchema
            ->expects($this->once())
            ->method('hasProperty')
            ->with('propertyWhichDoesntExist')
            ->willReturn((false));

        $this->validator->validate(new \StdClass());
    }

    #[Test]
    public function validatorThrowsExceptionIfThereIsNoIdentityProperty()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358459831);
        $this->prepareMockExpectations();
        $this->classSchema
            ->expects($this->once())
            ->method('getIdentityProperties')
            ->willReturn(([]));

        $this->validator->validate(new \StdClass());
    }

    #[Test]
    public function validatorThrowsExceptionOnMultipleOrmIdAnnotations()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358501745);
        $this->prepareMockExpectations();
        $this->classSchema
            ->expects($this->once())
            ->method('getIdentityProperties')
            ->willReturn((['foo']));
        $this->reflectionService
            ->expects($this->once())
            ->method('getPropertyNamesByAnnotation')
            ->with('FooClass', 'Doctrine\ORM\Mapping\Id')
            ->willReturn((['dummy array', 'with more than', 'one count']));

        $this->validator->validate(new \StdClass());
    }

    /**
     */
    protected function prepareMockExpectations()
    {
        $this->classSchema->expects($this->once())->method('getModelType')->willReturn((ClassSchema::MODELTYPE_ENTITY));
        $this->classSchema
            ->method('getClassName')
            ->willReturn(('FooClass'));
    }
}
