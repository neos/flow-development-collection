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

use Neos\Flow\Validation\Validator\NumberRangeValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the number range validator
 */
class NumberRangeValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = NumberRangeValidator::class;

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
    public function numberRangeValidatorReturnsNoErrorForASimpleIntegerInRange()
    {
        $this->validatorOptions(['minimum' => 0, 'maximum' => 1000]);

        $this->assertFalse($this->validator->validate(10.5)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsErrorForANumberOutOfRange()
    {
        $this->validatorOptions(['minimum' => 0, 'maximum' => 1000]);
        $this->assertTrue($this->validator->validate(1000.1)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsNoErrorForANumberInReversedRange()
    {
        $this->validatorOptions(['minimum' => 1000, 'maximum' => 0]);
        $this->assertFalse($this->validator->validate(100)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsErrorForAString()
    {
        $this->validatorOptions(['minimum' => 0, 'maximum' => 1000]);
        $this->assertTrue($this->validator->validate('not a number')->hasErrors());
    }
}
