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
 * Testcase for the string length validator
 *
 */
class StringValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\StringValidator::class;

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
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldValidateString()
    {
        $this->assertFalse($this->validator->validate('Hello World')->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfNumberIsGiven()
    {
        $this->assertTrue($this->validator->validate(42)->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven()
    {
        $className = 'TestClass' . md5(uniqid(mt_rand(), true));

        eval('
			class ' . $className . ' {
				public function __toString() {
					return "ASDF";
				}
			}
		');
        $object = new $className();
        $this->assertTrue($this->validator->validate($object)->hasErrors());
    }
}
