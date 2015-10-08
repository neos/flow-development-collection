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
 * Testcase for the raw validator
 *
 */
class RawValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\RawValidator::class;

    /**
     * @test
     */
    public function theRawValidatorAlwaysReturnsNoErrors()
    {
        $rawValidator = new \TYPO3\Flow\Validation\Validator\RawValidator(array());

        $this->assertFalse($rawValidator->validate('simple1expression')->hasErrors());
        $this->assertFalse($rawValidator->validate('')->hasErrors());
        $this->assertFalse($rawValidator->validate(null)->hasErrors());
        $this->assertFalse($rawValidator->validate(false)->hasErrors());
        $this->assertFalse($rawValidator->validate(new \ArrayObject())->hasErrors());
    }
}
