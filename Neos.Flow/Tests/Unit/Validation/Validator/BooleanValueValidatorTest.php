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

use Neos\Flow\Validation\Validator\BooleanValueValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the true validator
 */
class BooleanValueValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = BooleanValueValidator::class;

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsTrueAndNoOptionIsSet()
    {
        self::assertFalse($this->validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsFalseAndExpectedValueIsFalse()
    {
        $this->validatorOptions(['expectedValue' => false]);
        self::assertFalse($this->validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorIfTheGivenValueIsAString()
    {
        self::assertTrue($this->validator->validate('1')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorIfTheGivenValueIsFalse()
    {
        self::assertTrue($this->validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorIfTheGivenValueIsAnInteger()
    {
        self::assertTrue($this->validator->validate(1)->hasErrors());
    }
}
