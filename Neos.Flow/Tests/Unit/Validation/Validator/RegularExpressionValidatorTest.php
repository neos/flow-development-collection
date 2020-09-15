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

use Neos\Flow\Validation\Validator\RegularExpressionValidator;
use Neos\Flow\Validation;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the regular expression validator
 *
 */
class RegularExpressionValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = RegularExpressionValidator::class;

    /**
     * Looks empty - and that's the purpose: do not run the parent's setUp().
     */
    protected function setUp(): void
    {
    }

    /**
     * @test
     */
    public function validateThrowsExceptionIfExpressionIsEmpty()
    {
        $this->expectException(Validation\Exception\InvalidValidationOptionsException::class);
        $this->validatorOptions([]);
        $this->validator->validate('foo');
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->validatorOptions(['regularExpression' => '/^.*$/']);
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->validatorOptions(['regularExpression' => '/^.*$/']);
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function regularExpressionValidatorMatchesABasicExpressionCorrectly()
    {
        $this->validatorOptions(['regularExpression' => '/^simple[0-9]expression$/']);

        self::assertFalse($this->validator->validate('simple1expression')->hasErrors());
        self::assertTrue($this->validator->validate('simple1expressions')->hasErrors());
    }

    /**
     * @test
     */
    public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch()
    {
        $this->validatorOptions(['regularExpression' => '/^simple[0-9]expression$/']);
        $subject = 'some subject that will not match';
        $errors = $this->validator->validate($subject)->getErrors();
        self::assertEquals([new Validation\Error('The given subject did not match the pattern. Got: %1$s', 1221565130, [$subject])], $errors);
    }
}
