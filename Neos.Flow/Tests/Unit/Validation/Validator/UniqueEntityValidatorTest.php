<?php
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

/**
 * Testcase for the unique entity validator
 */
class UniqueEntityValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = UniqueEntityValidator::class;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * @see \Neos\Flow\Reflection\ClassSchema
     */
    protected $classSchema;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * @see \Neos\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->classSchema = $this->getMockBuilder(ClassSchema::class)->disableOriginalConstructor()->getMock();

        $this->reflectionService = $this->createMock(ReflectionService::class);
        $this->reflectionService->expects(self::any())->method('getClassSchema')->will(self::returnValue($this->classSchema));
        $this->inject($this->validator, 'reflectionService', $this->reflectionService);
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfValueIsNotAnObject()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358454270);
        $this->validator->validate('a string');
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfValueIsNotReflectedAtAll()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358454284);
        $this->classSchema->expects(self::once())->method('getModelType')->will(self::returnValue(null));

        $this->validator->validate(new \stdClass());
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfValueIsNotAFlowEntity()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358454284);
        $this->classSchema->expects(self::once())->method('getModelType')->will(self::returnValue(ClassSchema::MODELTYPE_VALUEOBJECT));

        $this->validator->validate(new \stdClass());
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfSetupPropertiesAreNotPresentInActualClass()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358960500);
        $this->prepareMockExpectations();
        $this->inject($this->validator, 'options', ['identityProperties' => ['propertyWhichDoesntExist']]);
        $this->classSchema
            ->expects(self::once())
            ->method('hasProperty')
            ->with('propertyWhichDoesntExist')
            ->will(self::returnValue(false));

        $this->validator->validate(new \StdClass());
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfThereIsNoIdentityProperty()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358459831);
        $this->prepareMockExpectations();
        $this->classSchema
            ->expects(self::once())
            ->method('getIdentityProperties')
            ->will(self::returnValue([]));

        $this->validator->validate(new \StdClass());
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionOnMultipleOrmIdAnnotations()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1358501745);
        $this->prepareMockExpectations();
        $this->classSchema
            ->expects(self::once())
            ->method('getIdentityProperties')
            ->will(self::returnValue(['foo']));
        $this->reflectionService
            ->expects(self::once())
            ->method('getPropertyNamesByAnnotation')
            ->with('FooClass', 'Doctrine\ORM\Mapping\Id')
            ->will(self::returnValue(['dummy array', 'with more than', 'one count']));

        $this->validator->validate(new \StdClass());
    }

    /**
     */
    protected function prepareMockExpectations()
    {
        $this->classSchema->expects(self::once())->method('getModelType')->will(self::returnValue(ClassSchema::MODELTYPE_ENTITY));
        $this->classSchema
            ->expects(self::any())
            ->method('getClassName')
            ->will(self::returnValue('FooClass'));
    }
}
