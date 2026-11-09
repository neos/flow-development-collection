<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit\Validation;

use Neos\Eel\Validation\ExpressionSyntaxValidator;
use Neos\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the ExpressionSyntaxValidator
 */
final class ExpressionSyntaxValidatorTest extends AbstractValidatorTestcase
{
    /**
     * @var string
     */
    protected $validatorClassName = ExpressionSyntaxValidator::class;

    #[Test]
    public function validExpressionPasses()
    {
        self::assertFalse(
            $this->validator->validate('foo.bar() * (18 + 2)')->hasErrors()
        );
    }

    #[Test]
    public function invalidExpressionIsConsideredErroneous()
    {
        self::assertTrue(
            $this->validator->validate('foo.bar( + (18 + 2)')->hasErrors()
        );
    }

    #[Test]
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
