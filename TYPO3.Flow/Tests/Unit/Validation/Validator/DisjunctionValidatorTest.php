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

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Validation\Validator\DisjunctionValidator;
use TYPO3\Flow\Validation\Validator\ValidatorInterface;
use TYPO3\Flow\Error;

/**
 * Testcase for the Disjunction Validator
 */
class DisjunctionValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validateReturnsNoErrorsIfOneValidatorReturnsNoError()
    {
        $validatorDisjunction = new DisjunctionValidator([]);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new Error\Result()));

        $errors = new Error\Result();
        $errors->addError(new Error\Error('Error', 123));

        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));

        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);

        $this->assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsAllErrorsIfAllValidatorsReturnErrrors()
    {
        $validatorDisjunction = new DisjunctionValidator([]);

        $error1 = new Error\Error('Error', 123);
        $error2 = new Error\Error('Error2', 123);

        $errors1 = new Error\Result();
        $errors1->addError($error1);
        $validatorObject = $this->createMock(ValidatorInterface::class);
        $validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors1));

        $errors2 = new Error\Result();
        $errors2->addError($error2);
        $secondValidatorObject = $this->createMock(ValidatorInterface::class);
        $secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors2));

        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);

        $this->assertEquals([$error1, $error2], $validatorDisjunction->validate('some subject')->getErrors());
    }
}
