<?php
namespace Neos\Eel\Tests\Unit\Validation;

use Neos\Eel\Validation\ExpressionSyntaxValidator;
use Neos\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase;

/**
 * Tests for the ExpressionSyntaxValidator
 */
class ExpressionSyntaxValidatorTest extends AbstractValidatorTestcase
{
    /**
     * @var string
     */
    protected $validatorClassName = ExpressionSyntaxValidator::class;

    /**
     * @test
     */
    public function validExpressionPasses()
    {
        self::assertFalse(
            $this->validator->validate('foo.bar() * (18 + 2)')->hasErrors()
        );
    }

    /**
     * @test
     */
    public function invalidExpressionIsConsideredErroneous()
    {
        self::assertTrue(
            $this->validator->validate('foo.bar( + (18 + 2)')->hasErrors()
        );
    }

    /**
     * @test
     */
    public function invalidExpressionGivesErrorPositionInformation()
    {
        $errorArguments =
            $this->validator->validate('foo.bar( + (18 + 2)')
                ->getFirstError()
                    ->getArguments();

        self::assertEquals('foo.bar( + (18 + 2)', $errorArguments[0]);
        self::assertEquals(7, $errorArguments[1]);
        self::assertEquals('( + (18 + 2)', $errorArguments[2]);
    }
}
