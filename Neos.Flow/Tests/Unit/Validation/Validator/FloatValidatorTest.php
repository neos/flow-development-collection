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

use Neos\Flow\Validation\Validator\FloatValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the float validator
 *
 */
class FloatValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = FloatValidator::class;

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
     * Data provider with valid floats
     *
     * @return array
     */
    public function validFloats()
    {
        return [
            [1029437.234726],
            ['123.45'],
            ['+123.45'],
            ['-123.45'],
            ['123.45e3'],
            [123.45e3]
        ];
    }

    /**
     * @test
     * @dataProvider validFloats
     */
    public function floatValidatorReturnsNoErrorsForAValidFloat($float)
    {
        $this->assertFalse($this->validator->validate($float)->hasErrors());
    }

    /**
     * Data provider with invalid floats
     *
     * @return array
     */
    public function invalidFloats()
    {
        return [
            [1029437],
            ['1029437'],
            ['foo.bar'],
            ['not a number']
        ];
    }

    /**
     * @test
     * @dataProvider invalidFloats
     */
    public function floatValidatorReturnsErrorForAnInvalidFloat($float)
    {
        $this->assertTrue($this->validator->validate($float)->hasErrors());
    }

    /**
     * test
     */
    public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        $this->assertEquals(1, count($this->validator->validate(123456)->getErrors()));
    }
}
