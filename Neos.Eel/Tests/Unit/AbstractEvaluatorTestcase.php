<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Eel\Context;
use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\EvaluationException;
use Neos\Eel\Package;
use Neos\Eel\ParserException;
use Neos\Eel\ProtectedContext;
use Neos\Eel\Tests\Unit\Fixtures\TestObject;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once "Fixtures/TestArrayIterator.php";
require_once "Fixtures/TestObject.php";

/**
 * Abstract evaluator test
 *
 * Is used to test both the compiling and interpreting Eel evaluators.
 */
abstract class AbstractEvaluatorTestcase extends UnitTestCase
{
    public static function integerLiterals(): \Iterator
    {
        $c = new Context();
        // So simple, so true
        yield ['1', $c, 1];
        // It all starts with zero
        yield ['0', $c, 0];
        // Very large number!
        yield ['2147483600', $c, 2147483600];
        // Don't be so negative
        yield ['-100', $c, -100];
    }

    public static function floatLiterals(): \Iterator
    {
        $c = new Context();
        yield ['1.0', $c, 1.0];
        yield ['3.141', $c, 3.141];
        yield ['-17.4', $c, -17.4];
    }

    public static function stringLiterals(): \Iterator
    {
        $c = new Context();
        // An empty string
        yield ['""', $c, ''];
        // Very basic
        yield ['"Hello world"', $c, 'Hello world'];
        // Escape not possible
        yield ['"Foo \"Bar\""', $c, 'Foo "Bar"'];
        // Single quotes ftw
        yield ['\'\'', $c, ''];
        // Single quotes ftw
        yield ['\'Foo\'', $c, 'Foo'];
        // Mixed quote salad
        yield ['\'"Foo" Bar\'', $c, '"Foo" Bar'];
    }

    public static function stringConcatenations(): \Iterator
    {
        $c = new Context(['foo' => 'bar']);
        // Just concatenate two strings
        yield ['"a" + "b"', $c, 'ab'];
        // Concatenate a string and an integer
        yield ['2 + "b"', $c, '2b'];
        // Concatenate a wrapped element and a string
        yield ['foo + "b"', $c, 'barb'];
        // Concatenate three elements
        yield ['foo + " x " + foo', $c, 'bar x bar'];
    }

    public static function notExpressions(): \Iterator
    {
        $c = new Context();
        // Not one is false
        yield ['!1', $c, false];
        // Not an empty string is true
        yield ['!""', $c, true];
        // Some whitespace allowed
        yield ['!0', $c, true];
        // A not can be a word
        yield ['not 0', $c, true];
    }

    public static function comparisonExpressions(): \Iterator
    {
        $c = new Context([
            'answer' => 42
        ]);
        yield ['1==0', $c, false];
        yield ['1==1', $c, true];
        yield ['0 == 0', $c, true];
        // It's strict
        yield ['0==""', $c, false];
        // Quoting doesn't matter
        yield ['"Foo"==\'Foo\'', $c, true];
        // Whitespace okay!
        yield ['1> 0', $c, true];
        // Whitespace okay!
        yield ['1 <0', $c, false];
        // Parenthesed comparisons
        yield ['(0 > 1) < (0 < 1)', $c, true];
        // Comparisons and variables
        yield ['answer > 1', $c, true];
        yield ['answer==  42', $c, true];
        // Less than equal and greater than equal
        yield ['1<= 0', $c, false];
        yield ['1 >=1', $c, true];
        // Inequality
        yield ['1!=1', $c, false];
        yield ['1!=true', $c, true];
        yield ['answer != 7', $c, true];
    }

    public static function calculationExpressions(): \Iterator
    {
        $c = new Context([
            'answer' => 42,
            'deeply' => [
                'nested' => [
                    'value' => 2
                ]
            ]
        ]);
        // Very basic
        yield ['1 + 1', $c, 2];
        yield ['1 - 1', $c, 0];
        yield ['2*2', $c, 4];
        // Multiple calc with precedence
        yield ['1 + 2 * 3 + 4 / 2 + 2', $c, 11];
        yield ['(1 + 2) * 3 + 4 / (2 + 2)', $c, 10];
        // Calculation with variables
        yield ['2* answer', $c, 84];
        // Calculation with nested context
        yield ['deeply.nested.value - 1', $c, 1];
    }

    public static function combinedExpressions(): \Iterator
    {
        $c = new Context();
        // Calculations before comparisons
        yield ['1 + 2 > 3', $c, false];
        // Calculations before comparisons
        yield ['2 * 1 == 3 - 1', $c, true];
        // Comparison on left side work too
        yield ['1 < 1 + 1', $c, true];
    }

    public static function booleanExpressions(): \Iterator
    {
        $c = new Context([
            'trueVar' => true,
            'falseVar' => false
        ]);
        // Boolean literals work
        yield ['false', $c, false];
        yield ['true', $c, true];
        // Conjunction before Disjunction
        yield ['true && true || false && false', $c, true];
        yield ['true && false || false && true', $c, false];
        yield ['1 < 2 && 2 > 1', $c, true];
        yield ['!1 < 2', $c, true];
        yield ['!(1 < 2)', $c, false];
        // Named and symbolic operators can be mixed
        yield ['true && true and false or false', $c, false];
        // Using variables and literals
        yield ['trueVar || false', $c, true];
        yield ['trueVar && true', $c, true];
        yield ['falseVar || false', $c, false];
        yield ['falseVar && true', $c, false];
        // JavaScript semantics of boolean operators
        yield ['null || "foo"', $c, 'foo'];
        yield ['0 || "foo"', $c, 'foo'];
        yield ['0 || ""', $c, ''];
        yield ['"bar" || "foo"', $c, 'bar'];
        yield ['"foo" && "bar"', $c, 'bar'];
        yield ['"" && false', $c, ''];
        yield ['"Bar" && 0', $c, 0];
        yield ['0 && ""', $c, 0];
    }

    public static function objectPathOnArrayExpressions(): \Iterator
    {
        // Wrap a value inside a context
        $c = new Context([
            'foo' => 42,
            'bar' => [
                'baz' => 'Hello',
                'a1' => [
                    'b2' => 'Nested'
                ]
            ],
            'another' => [
                'path' => 'b2'
            ],
            'numeric' => ['a', 'b', 'c']
        ]);
        // Undefined variables are NULL with the default context
        yield ['unknwn', $c, null];
        // Simple variable statement
        yield ['foo', $c, 42];
        // Simple object path
        yield ['bar.baz', $c, 'Hello'];
        // Dynamic array like access of properties by another object path (awesome!!!)
        yield ['bar.a1[another.path]', $c, 'Nested'];
        // Offset access with invalid path is NULL
        yield ['bar.a1[unknwn.path]', $c, null];
        // Offset access with integers
        yield ['numeric[1]', $c, 'b'];
        yield ['numeric[0]', $c, 'a'];
    }

    public static function objectPathOnObjectExpressions(): array
    {
        $obj = new TestObject();
        $obj->setProperty('Test');
        $nested = new TestObject();
        $nested->setProperty($obj);
        // Wrap an object inside a context
        $c = new Context([
            'obj' => $obj,
            'nested' => $nested
        ]);
        return [
            // Access object properties by getter
            ['obj.property', $c, 'Test'],
            // Access nested objects
            ['nested.property.property', $c, 'Test'],
            // Call a method on an object
            ['obj.callMe("Foo")', $c, 'Hello, Foo!'],
        ];
    }

    public static function methodCallExpressions(): array
    {
        // Wrap an array with functions inside a context
        $contextArray = [
            'count' => function ($array) {
                return count($array);
            },
            'pow' => function ($base, $exp) {
                return pow($base, $exp);
            },
            'funcs' => [
                'dup' => function ($array) {
                    return array_map(static function ($item) {
                        return $item * 2;
                    }, $array);
                }
            ],
            'foo' => function () {
                return [
                    'a' => 'a1',
                    'b' => 'b1',
                ];
            },

            'arr' => ['a' => 1, 'b' => 2, 'c' => 3],
            'someVariable' => 'b'
        ];
        $c = new Context($contextArray);

        $protectedContext = new ProtectedContext($contextArray);
        $protectedContext->allow('*');
        return [
            // Call first-level method
            ['count(arr)', $c, 3],
            // Method with multiple arguments
            ['pow(2, 8)', $c, 256],
            // Combine method call and operation
            ['count(arr) + 1', $c, 4],
            // Nested method call and operation inside an method call
            ['pow(2, count(arr) + 1)', $c, 16],
            // Nest method calls and object paths
            ['funcs.dup(arr).b', $c, 4],

            // Nest method calls and array access
            ['funcs.dup(arr)[someVariable]', $c, 4],
            ['foo()[someVariable]', $c, 'b1'],
            // Nest method calls and array access with protected context
            ['foo()[someVariable]', $protectedContext, 'b1'],
            // Method call on NULL value returns NULL
            ['unknwn.func()', $c, null],
        ];
    }

    public static function arrayLiteralExpressions(): \Iterator
    {
        $c = new Context([
            'test' => function ($string) {
                return 'test|' . $string . '|';
            },
            'foo' => [
                'baz' => 'Hello'
            ],
            'bar' => 'baz'
        ]);
        // Empty array
        yield ['[]', $c, []];
        // Simple array with integer literals
        yield ['[1, 2, 3]', $c, [1, 2, 3]];
        // Nested array literals
        yield ['[[1, 2], 3, 4]', $c, [[1, 2], 3, 4]];
        // Nested expressions in array literal
        yield ['[[foo[bar], 2], test("a"), 4]', $c, [['Hello', 2], 'test|a|', 4]];
        // Simple array, padded with whitespace
        yield ['[ 1, 2, 3 ]', $c, [1, 2, 3]];
        // Simple multiline array
        yield ['[
                1,
                2,
                3
            ]', $c, [1, 2, 3]];
    }

    public static function objectLiteralExpressions(): \Iterator
    {
        $c = new Context([
        ]);
        // Empty object
        yield ['{}', $c, []];
        // Simple object literal with unquoted key
        yield ['{foo: "bar", bar: "baz"}', $c, ['foo' => 'bar', 'bar' => 'baz']];
        // Simple object literal with differently quoted keys
        yield ['{"foo": "bar", \'bar\': "baz"}', $c, ['foo' => 'bar', 'bar' => 'baz']];
        // Nested object literals with unquoted key
        yield ['{foo: "bar", bar: {baz: "quux"}}', $c, ['foo' => 'bar', 'bar' => ['baz' => 'quux']]];
        // Simple object literal, padded with whitespace
        yield ['{ foo: "bar", bar: "baz" }', $c, ['foo' => 'bar', 'bar' => 'baz']];
        // Simple multiline object literal
        yield ['{
                foo: "bar",
                bar: "baz"
            }', $c, ['foo' => 'bar', 'bar' => 'baz']];
    }

    public static function conditionalOperatorExpressions(): \Iterator
    {
        $c = new Context([
            'answer' => 42,
            'trueVar' => true,
            'a' => 5,
            'b' => 10
        ]);
        // Simple ternary operator expression (condition)
        yield ['true ? 1 : 2', $c, 1];
        // Ternary operator using variables
        yield ['trueVar ? answer : false', $c, 42];
        yield ['!trueVar ? false : answer', $c, 42];
        yield ['a < b ? 1 : 2', $c, 1];
        // Ternary operator with nested expressions
        yield ['a < b ? 1 + a : 2 + b', $c, 6];
        yield ['a > b ? 1 + a : 2 + b', $c, 12];
    }

    #[DataProvider('integerLiterals')]
    #[Test]
    public function integerLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('floatLiterals')]
    #[Test]
    public function floatLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('stringLiterals')]
    #[Test]
    public function stringLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('stringConcatenations')]
    #[Test]
    public function stringConcatenationsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('notExpressions')]
    #[Test]
    public function notExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('comparisonExpressions')]
    #[Test]
    public function comparisonExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('calculationExpressions')]
    #[Test]
    public function calculationExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('combinedExpressions')]
    #[Test]
    public function combinedExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('objectPathOnArrayExpressions')]
    #[Test]
    public function objectPathOnArrayExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('objectPathOnObjectExpressions')]
    #[Test]
    public function objectPathOnObjectExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('methodCallExpressions')]
    #[Test]
    public function methodCallExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[Test]
    public function methodCallOfUndefinedFunctionThrowsException(): void
    {
        $this->expectException(EvaluationException::class);
        $c = new Context([
            'arr' => [
                'func' => function ($arg) {
                    return 42;
                }
            ]
        ]);
        $this->assertEvaluated(null, 'arr.funk("title")', $c);
    }

    #[Test]
    public function methodCallOfUnknownMethodThrowsException(): void
    {
        $this->expectException(EvaluationException::class);
        $o = new TestObject();

        $c = new Context([
            'context' => $o
        ]);
        $this->assertEvaluated(null, 'context.callYou("title")', $c);
    }

    #[DataProvider('booleanExpressions')]
    #[Test]
    public function booleanExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('arrayLiteralExpressions')]
    #[Test]
    public function arrayLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('objectLiteralExpressions')]
    #[Test]
    public function objectLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    #[DataProvider('conditionalOperatorExpressions')]
    #[Test]
    public function conditionalOperatorsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    public static function invalidExpressions(): \Iterator
    {
        // Completely insane expression
        yield ['NULL ---invalid---'];
        // Wrong parens
        yield ['a * (5 + a))'];
        yield ['(a * 5 + b'];
        // Incomplete object path
        yield ['a.b. < 1'];
        // Invalid quoted strings
        yield ['"a "super\" \'thing\'"'];
    }

    #[DataProvider('invalidExpressions')]
    #[Test]
    public function invalidExpressionsThrowExceptions(string $expression): void
    {
        $this->expectException(ParserException::class);
        $this->assertEvaluated(false, $expression, new Context());
    }

    #[Test]
    public function expressionStartingWithWhitespaceWorkAsExpected(): void
    {
        $context = new Context(['variable' => 1]);
        $this->assertEvaluated(1, ' variable', $context);
    }

    #[Test]
    public function expressionEndingWithWhitespaceWorkAsExpected(): void
    {
        $context = new Context(['variable' => 1]);
        $this->assertEvaluated(1, 'variable ', $context);
    }

    /**
     * Assert that the expression is evaluated to the expected result
     * under the given context. It also ensures that the Eel expression is
     * recognized using the predefined regular expression.
     */
    protected function assertEvaluated(mixed $expected, string $expression, Context $context): void
    {
        $evaluator = $this->createEvaluator();
        self::assertSame($expected, $evaluator->evaluate($expression, $context));

        $wrappedExpression = '${' . $expression . '}';
        self::assertSame(1, preg_match(Package::EelExpressionRecognizer, $wrappedExpression), 'The wrapped expression ' . $wrappedExpression . ' was not detected as Eel expression');
    }

    /**
     * @return EelEvaluatorInterface
     */
    abstract protected function createEvaluator();
}
