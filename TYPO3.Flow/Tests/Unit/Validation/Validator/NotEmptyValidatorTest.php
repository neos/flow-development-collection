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
 * Testcase for the not empty validator
 *
 */
class NotEmptyValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\NotEmptyValidator::class;

    /**
     * @test
     */
    public function notEmptyValidatorReturnsNoErrorForASimpleString()
    {
        $this->assertFalse($this->validator->validate('a not empty string')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyString()
    {
        $this->assertTrue($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForANullValue()
    {
        $this->assertTrue($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyArray()
    {
        $this->assertTrue($this->validator->validate(array())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyCountableObject()
    {
        $this->assertTrue($this->validator->validate(new \SplObjectStorage())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject()
    {
        $this->assertEquals(1, count($this->validator->validate('')->getErrors()));
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForANullValue()
    {
        $this->assertEquals(1, count($this->validator->validate(null)->getErrors()));
    }
}
