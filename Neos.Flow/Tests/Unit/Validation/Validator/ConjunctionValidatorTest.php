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
use Neos\Error\Messages as Error;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Exception\NoSuchValidatorException;
use Neos\Flow\Validation\Validator\ConjunctionValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Conjunction Validator
 */
final class ConjunctionValidatorTest extends UnitTestCase
{
    #[Test]
    public function addingValidatorsToAJunctionValidatorWorks()
    {
        $proxyClassName = $this->buildAccessibleProxy(ConjunctionValidator::class);
        $conjunctionValidator = new $proxyClassName([]);

        $mockValidator = $this->createStub(ValidatorInterface::class);
        $conjunctionValidator->addValidator($mockValidator);
        self::assertTrue($conjunctionValidator->_get('validators')->contains($mockValidator));
    }

    #[Test]
    public function allValidatorsInTheConjunctionAreCalledEvenIfOneReturnsError()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->expects($this->once())->method('validate')->willReturn((new Error\Result()));

        $errors = new Error\Result();
        $errors->addError(new Error\Error('Error', 123));
        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->expects($this->once())->method('validate')->willReturn(($errors));

        $thirdValidatorObject = $this->createMock(ValidatorInterface::class);
        $thirdValidatorObject->expects($this->once())->method('validate')->willReturn((new Error\Result()));

        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);
        $validatorConjunction->addValidator($thirdValidatorObject);

        $validatorConjunction->validate('some subject');
    }

    #[Test]
    public function validatorConjunctionReturnsNoErrorsIfAllJunctionedValidatorsReturnNoErrors()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->method('validate')->willReturn((new Error\Result()));

        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->method('validate')->willReturn((new Error\Result()));

        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);

        self::assertFalse($validatorConjunction->validate('some subject')->hasErrors());
    }

    #[Test]
    public function validatorConjunctionReturnsErrorsIfOneValidatorReturnsErrors()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);

        $errors = new Error\Result();
        $errors->addError(new Error\Error('Error', 123));

        $validatorObject->method('validate')->willReturn(($errors));

        $validatorConjunction->addValidator($validatorObject);

        self::assertTrue($validatorConjunction->validate('some subject')->hasErrors());
    }

    #[Test]
    public function removingAValidatorOfTheValidatorConjunctionWorks()
    {
        $validatorConjunction = $this->getAccessibleMock(ConjunctionValidator::class, [], [[]], '', true);

        $validator1 = $this->createStub(ValidatorInterface::class);
        $validator2 = $this->createStub(ValidatorInterface::class);

        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);

        $validatorConjunction->removeValidator($validator1);

        self::assertFalse($validatorConjunction->_get('validators')->contains($validator1));
        self::assertTrue($validatorConjunction->_get('validators')->contains($validator2));
    }

    #[Test]
    public function removingANotExistingValidatorIndexThrowsException()
    {
        $this->expectException(NoSuchValidatorException::class);
        $validatorConjunction = new ConjunctionValidator([]);
        $validator = $this->createStub(ValidatorInterface::class);
        $validatorConjunction->removeValidator($validator);
    }

    #[Test]
    public function countReturnesTheNumberOfValidatorsContainedInTheConjunction()
    {
        $validatorConjunction = new ConjunctionValidator([]);

        $validator1 = $this->createStub(ValidatorInterface::class);
        $validator2 = $this->createStub(ValidatorInterface::class);

        self::assertCount(0, $validatorConjunction);

        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);

        self::assertCount(2, $validatorConjunction);
    }
}
