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
use Neos\Flow\Validation\Validator\FloatValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the float validator
 *
 */
final class FloatValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = FloatValidator::class;

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

    /**
     * Data provider with valid floats
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function validFloats(): \Iterator
    {
        yield [1029437.234726];
        yield ['123.45'];
        yield ['+123.45'];
        yield ['-123.45'];
        yield ['123.45e3'];
        yield [123.45e3];
    }

    #[DataProvider('validFloats')]
    #[Test]
    public function floatValidatorReturnsNoErrorsForAValidFloat($float)
    {
        self::assertFalse($this->validator->validate($float)->hasErrors());
    }

    /**
     * Data provider with invalid floats
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidFloats(): \Iterator
    {
        yield [1029437];
        yield ['1029437'];
        yield ['foo.bar'];
        yield ['not a number'];
    }

    #[DataProvider('invalidFloats')]
    #[Test]
    public function floatValidatorReturnsErrorForAnInvalidFloat($float)
    {
        self::assertTrue($this->validator->validate($float)->hasErrors());
    }

    /**
     * test
     */
    public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        self::assertCount(1, $this->validator->validate(123456)->getErrors());
    }
}
