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
use Neos\Flow\Validation\Validator\DisjunctionValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Disjunction Validator
 */
final class DisjunctionValidatorTest extends UnitTestCase
{
    #[Test]
    public function validateReturnsNoErrorsIfOneValidatorReturnsNoError()
    {
        $validatorDisjunction = new DisjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->method('validate')->willReturn((new Error\Result()));

        $errors = new Error\Result();
        $errors->addError(new Error\Error('Error', 123));

        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->method('validate')->willReturn(($errors));

        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);

        self::assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
    }

    #[Test]
    public function validateReturnsAllErrorsIfAllValidatorsReturnErrrors()
    {
        $validatorDisjunction = new DisjunctionValidator([]);

        $error1 = new Error\Error('Error', 123);
        $error2 = new Error\Error('Error2', 123);

        $errors1 = new Error\Result();
        $errors1->addError($error1);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->method('validate')->willReturn(($errors1));

        $errors2 = new Error\Result();
        $errors2->addError($error2);
        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->method('validate')->willReturn(($errors2));

        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);

        self::assertEquals([$error1, $error2], $validatorDisjunction->validate('some subject')->getErrors());
    }
}
