<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the true validator
 *
 */
class BooleanValueValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\BooleanValueValidator::class;

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsTrueAndNoOptionIsSet()
    {
        $this->assertFalse($this->validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsFalseAndExpectedValueIsFalse()
    {
        $this->validatorOptions(array('expectedValue' => false));
        $this->assertFalse($this->validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorIfTheGivenValueIsAString()
    {
        $this->assertTrue($this->validator->validate('1')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorIfTheGivenValueIsFalse()
    {
        $this->assertTrue($this->validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorIfTheGivenValueIsAnInteger()
    {
        $this->assertTrue($this->validator->validate(1)->hasErrors());
    }
}
