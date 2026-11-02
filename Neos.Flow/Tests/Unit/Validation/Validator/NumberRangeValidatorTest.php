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
use Neos\Flow\Validation\Validator\NumberRangeValidator;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the number range validator
 */
final class NumberRangeValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = NumberRangeValidator::class;

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    #[Test]
    public function numberRangeValidatorReturnsNoErrorForASimpleIntegerInRange()
    {
        $this->validatorOptions(['minimum' => 0, 'maximum' => 1000]);

        self::assertFalse($this->validator->validate(10.5)->hasErrors());
    }

    #[Test]
    public function numberRangeValidatorReturnsErrorForANumberOutOfRange()
    {
        $this->validatorOptions(['minimum' => 0, 'maximum' => 1000]);
        self::assertTrue($this->validator->validate(1000.1)->hasErrors());
    }

    #[Test]
    public function numberRangeValidatorReturnsNoErrorForANumberInReversedRange()
    {
        $this->validatorOptions(['minimum' => 1000, 'maximum' => 0]);
        self::assertFalse($this->validator->validate(100)->hasErrors());
    }

    #[Test]
    public function numberRangeValidatorReturnsErrorForAString()
    {
        $this->validatorOptions(['minimum' => 0, 'maximum' => 1000]);
        self::assertTrue($this->validator->validate('not a number')->hasErrors());
    }
}
