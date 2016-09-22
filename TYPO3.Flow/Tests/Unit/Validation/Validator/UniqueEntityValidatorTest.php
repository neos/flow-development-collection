<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Reflection\ClassSchema;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\Flow\Validation\Validator\UniqueEntityValidator;

/**
 * Testcase for the unique entity validator
 */
class UniqueEntityValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = UniqueEntityValidator::class;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     * @see \TYPO3\Flow\Reflection\ClassSchema
     */
    protected $classSchema;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     * @see \TYPO3\Flow\Reflection\ReflectionService
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
     */
    public function validatorThrowsExceptionIfValueIsNotAnObject()
    {
        $this->setExpectedException(InvalidValidationOptionsException::class, '', 1358454270);
        $this->validator->validate('a string');
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfValueIsNotReflectedAtAll()
    {
        $this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(null));

        $this->setExpectedException(InvalidValidationOptionsException::class, '', 1358454284);
        $this->validator->validate(new \stdClass());
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfValueIsNotAFlowEntity()
    {
        $this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(ClassSchema::MODELTYPE_VALUEOBJECT));

        $this->setExpectedException(InvalidValidationOptionsException::class, '', 1358454284);
        $this->validator->validate(new \stdClass());
    }

    /**
     * @test
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

        $this->setExpectedException(InvalidValidationOptionsException::class, '', 1358960500);
        $this->validator->validate(new \StdClass());
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionIfThereIsNoIdentityProperty()
    {
        $this->prepareMockExpectations();
        $this->classSchema
            ->expects($this->once())
            ->method('getIdentityProperties')
            ->will($this->returnValue([]));

        $this->setExpectedException(InvalidValidationOptionsException::class, '', 1358459831);
        $this->validator->validate(new \StdClass());
    }

    /**
     * @test
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

        $this->setExpectedException(InvalidValidationOptionsException::class, '', 1358501745);
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
