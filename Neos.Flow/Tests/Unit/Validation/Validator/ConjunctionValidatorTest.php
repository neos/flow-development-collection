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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Validator\ConjunctionValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Error\Messages as Error;

/**
 * Testcase for the Conjunction Validator
 */
class ConjunctionValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addingValidatorsToAJunctionValidatorWorks()
    {
        $proxyClassName = $this->buildAccessibleProxy(ConjunctionValidator::class);
        $conjunctionValidator = new $proxyClassName([]);

        $mockValidator = $this->createMock(ValidatorInterface::class);
        $conjunctionValidator->addValidator($mockValidator);
        $this->assertTrue($conjunctionValidator->_get('validators')->contains($mockValidator));
    }

    /**
     * @test
     */
    public function allValidatorsInTheConjunctionAreCalledEvenIfOneReturnsError()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->expects($this->once())->method('validate')->will($this->returnValue(new Error\Result()));

        $errors = new Error\Result();
        $errors->addError(new Error\Error('Error', 123));
        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->expects($this->once())->method('validate')->will($this->returnValue($errors));

        $thirdValidatorObject = $this->createMock(ValidatorInterface::class);
        $thirdValidatorObject->expects($this->once())->method('validate')->will($this->returnValue(new Error\Result()));

        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);
        $validatorConjunction->addValidator($thirdValidatorObject);

        $validatorConjunction->validate('some subject');
    }

    /**
     * @test
     */
    public function validatorConjunctionReturnsNoErrorsIfAllJunctionedValidatorsReturnNoErrors()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new Error\Result()));

        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue(new Error\Result()));

        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);

        $this->assertFalse($validatorConjunction->validate('some subject')->hasErrors());
    }

    /**
     * @test
     */
    public function validatorConjunctionReturnsErrorsIfOneValidatorReturnsErrors()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);

        $errors = new Error\Result();
        $errors->addError(new Error\Error('Error', 123));

        $validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));

        $validatorConjunction->addValidator($validatorObject);

        $this->assertTrue($validatorConjunction->validate('some subject')->hasErrors());
    }

    /**
     * @test
     */
    public function removingAValidatorOfTheValidatorConjunctionWorks()
    {
        $validatorConjunction = $this->getAccessibleMock(ConjunctionValidator::class, ['dummy'], [[]], '', true);

        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator2 = $this->createMock(ValidatorInterface::class);

        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);

        $validatorConjunction->removeValidator($validator1);

        $this->assertFalse($validatorConjunction->_get('validators')->contains($validator1));
        $this->assertTrue($validatorConjunction->_get('validators')->contains($validator2));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\NoSuchValidatorException
     */
    public function removingANotExistingValidatorIndexThrowsException()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validator = $this->createMock(ValidatorInterface::class);
        $validatorConjunction->removeValidator($validator);
    }

    /**
     * @test
     */
    public function countReturnesTheNumberOfValidatorsContainedInTheConjunction()
    {
        $validatorConjunction = new ConjunctionValidator([]);

        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator2 = $this->createMock(ValidatorInterface::class);

        $this->assertSame(0, count($validatorConjunction));

        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);

        $this->assertSame(2, count($validatorConjunction));
    }
}
