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
use Neos\Flow\Validation\Validator\UniqueEntityValidator;

/**
 * Testcase for the unique entity validator
 */
class UniqueEntityValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = UniqueEntityValidator::class;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     * @see \Neos\Flow\Reflection\ClassSchema
     */
    protected $classSchema;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     * @see \Neos\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     */
    public function setUp()
    {
        parent::setUp();
        $this->classSchema = $this->getMockBuilder(ClassSchema::class)->disableOriginalConstructor()->getMock();

        $this->reflectionService = $this->createMock(ReflectionService::class);
        $this->reflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($this->classSchema));
        $this->inject($this->validator, 'reflectionService', $this->reflectionService);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationOptionsException
     * @expectedExceptionCode 1358454270
     */
    public function validatorThrowsExceptionIfValueIsNotAnObject()
    {
        $this->validator->validate('a string');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationOptionsException
     * @expectedExceptionCode 1358454284
     */
    public function validatorThrowsExceptionIfValueIsNotReflectedAtAll()
    {
        $this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(null));

        $this->validator->validate(new \stdClass());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationOptionsException
     * @expectedExceptionCode 1358454284
     */
    public function validatorThrowsExceptionIfValueIsNotAFlowEntity()
    {
        $this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(ClassSchema::MODELTYPE_VALUEOBJECT));

        $this->validator->validate(new \stdClass());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationOptionsException
     * @expectedExceptionCode 1358960500
     */
    public function validatorThrowsExceptionIfSetupPropertiesAreNotPresentInActualClass()
    {
        $this->prepareMockExpectations();
        $this->inject($this->validator, 'options', ['identityProperties' => ['propertyWhichDoesntExist']]);
        $this->classSchema
            ->expects($this->once())
            ->method('hasProperty')
            ->with('propertyWhichDoesntExist')
            ->will($this->returnValue(false));

        $this->validator->validate(new \StdClass());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationOptionsException
     * @expectedExceptionCode 1358459831
     */
    public function validatorThrowsExceptionIfThereIsNoIdentityProperty()
    {
        $this->prepareMockExpectations();
        $this->classSchema
            ->expects($this->once())
            ->method('getIdentityProperties')
            ->will($this->returnValue([]));

        $this->validator->validate(new \StdClass());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationOptionsException
     * @expectedExceptionCode 1358501745
     */
    public function validatorThrowsExceptionOnMultipleOrmIdAnnotations()
    {
        $this->prepareMockExpectations();
        $this->classSchema
            ->expects($this->once())
            ->method('getIdentityProperties')
            ->will($this->returnValue(['foo']));
        $this->reflectionService
            ->expects($this->once())
            ->method('getPropertyNamesByAnnotation')
            ->with('FooClass', 'Doctrine\ORM\Mapping\Id')
            ->will($this->returnValue(['dummy array', 'with more than', 'one count']));

        $this->validator->validate(new \StdClass());
    }

    /**
     */
    protected function prepareMockExpectations()
    {
        $this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(ClassSchema::MODELTYPE_ENTITY));
        $this->classSchema
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('FooClass'));
    }
}
