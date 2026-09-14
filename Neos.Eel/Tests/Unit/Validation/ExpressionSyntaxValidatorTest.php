<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit\Validation;

use Neos\Eel\Validation\ExpressionSyntaxValidator;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the ExpressionSyntaxValidator
 */
final class ExpressionSyntaxValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = ExpressionSyntaxValidator::class;

    /**
     *
     * @var ValidatorInterface
     */
    protected $validator;

    protected function setUp(): void
    {
        $this->validator = $this->getValidator();
    }

    protected function getValidator($options = [])
    {
        return $this->getAccessibleMock($this->validatorClassName, [], [$options], '', true);
    }

    protected function validatorOptions($options)
    {
        $this->validator = $this->getValidator($options);
    }

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
