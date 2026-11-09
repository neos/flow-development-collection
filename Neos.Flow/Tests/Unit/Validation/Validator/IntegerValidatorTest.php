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
use Neos\Flow\Validation\Validator\IntegerValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the integer validator
 *
 */
final class IntegerValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = IntegerValidator::class;

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
     * Data provider with valid integers
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function validIntegers(): \Iterator
    {
        yield [1029437];
        yield ['12345'];
        yield ['+12345'];
        yield ['-12345'];
    }

    #[DataProvider('validIntegers')]
    #[Test]
    public function integerValidatorReturnsNoErrorsForAValidInteger($integer)
    {
        self::assertFalse($this->validator->validate($integer)->hasErrors());
    }

    /**
     * Data provider with invalid integers
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidIntegers(): \Iterator
    {
        yield ['not a number'];
        yield [3.1415];
        yield ['12345.987'];
    }

    #[DataProvider('invalidIntegers')]
    #[Test]
    public function integerValidatorReturnsErrorForAnInvalidInteger($invalidInteger)
    {
        self::assertTrue($this->validator->validate($invalidInteger)->hasErrors());
    }

    #[Test]
    public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        self::assertCount(1, $this->validator->validate('not a number')->getErrors());
    }
}
