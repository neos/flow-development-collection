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

use Neos\Flow\Validation\Validator\CountValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the count validator
 *
 */
class CountValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = CountValidator::class;

    #[Test]
    public function countValidatorReturnsNoErrorsIfTheGivenValueIsNull()
    {
        $this->validatorOptions(['minimum' => 1, 'maximum' => 10]);
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    #[Test]
    public function countValidatorReturnsNoErrorsIfTheGivenStringIsEmpty()
    {
        $this->validatorOptions(['minimum' => 1, 'maximum' => 10]);
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @return array
     */
    public static function countables()
    {
        $splObjectStorage = new \SplObjectStorage();
        $splObjectStorage->attach(new \stdClass());
        return [
            [['Foo', 'Bar']],
            [new \ArrayObject(['Baz', 'Quux'])],
            [$splObjectStorage]
        ];
    }

    #[DataProvider('countables')]
    #[Test]
    public function countValidatorReturnsNoErrorsForValidCountables($countable)
    {
        $this->validatorOptions(['minimum' => 1, 'maximum' => 10]);
        self::assertFalse($this->validator->validate($countable)->hasErrors());
    }

    #[DataProvider('countables')]
    #[Test]
    public function countValidatorReturnsErrorsForInvalidCountables($countable)
    {
        $this->validatorOptions(['minimum' => 5, 'maximum' => 10]);
        self::assertTrue($this->validator->validate($countable)->hasErrors());
    }

    /**
     */
    public static function nonCountables()
    {
        $splObjectStorage = new \SplObjectStorage();
        $splObjectStorage->attach(new \stdClass());
        return [
            ['Bar'],
            [1],
            [new \stdClass()]
        ];
    }

    #[DataProvider('nonCountables')]
    #[Test]
    public function countValidatorReturnsErrorsForNonCountables($nonCountable)
    {
        self::assertTrue($this->validator->validate($nonCountable)->hasErrors());
    }
}
