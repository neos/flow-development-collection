<?php
namespace TYPO3\Eel\Tests\Unit\Validation;

use TYPO3\Eel\Validation\ExpressionSyntaxValidator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase;

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
        $this->assertFalse(
            $this->validator->validate('foo.bar() * (18 + 2)')->hasErrors());
    }

    /**
     * @test
     */
    public function invalidExpressionIsConsideredErroneous()
    {
        $this->assertTrue(
            $this->validator->validate('foo.bar( + (18 + 2)')->hasErrors());
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

        $this->assertEquals('foo.bar( + (18 + 2)', $errorArguments[0]);
        $this->assertEquals(7, $errorArguments[1]);
        $this->assertEquals('( + (18 + 2)', $errorArguments[2]);
    }
}
