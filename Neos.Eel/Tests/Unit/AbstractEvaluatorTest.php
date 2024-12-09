<?php
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
use Neos\Eel\ParserException;
use Neos\Eel\Tests\Unit\Fixtures\TestObject;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Abstract evaluator test
 *
 * Is used to test both the compiling and interpreting Eel evaluators.
 */
abstract class AbstractEvaluatorTest extends UnitTestCase
{
    public static function integerLiterals(): array
    {
        $c = new Context();
        return [
            // So simple, so true
            ['1', $c, 1],
            // It all starts with zero
            ['0', $c, 0],
            // Very large number!
            ['2147483600', $c, 2147483600],
            // Don't be so negative
            ['-100', $c, -100],
        ];
    }

    public static function floatLiterals(): array
    {
        $c = new Context();
        return [
            ['1.0', $c, 1.0],
            ['3.141', $c, 3.141],
            ['-17.4', $c, -17.4],
        ];
    }

    public static function stringLiterals(): array
    {
        $c = new Context();
        return [
            // An empty string
            ['""', $c, ''],
            // Very basic
            ['"Hello world"', $c, 'Hello world'],
            // Escape not possible
            ['"Foo \"Bar\""', $c, 'Foo "Bar"'],
            // Single quotes ftw
            ['\'\'', $c, ''],
            // Single quotes ftw
            ['\'Foo\'', $c, 'Foo'],
            // Mixed quote salad
            ['\'"Foo" Bar\'', $c, '"Foo" Bar'],
        ];
    }

    public static function stringConcatenations(): array
    {
        $c = new Context(['foo' => 'bar']);
        return [
            // Just concatenate two strings
            ['"a" + "b"', $c, 'ab'],
            // Concatenate a string and an integer
            ['2 + "b"', $c, '2b'],
            // Concatenate a wrapped element and a string
            ['foo + "b"', $c, 'barb'],
            // Concatenate three elements
            ['foo + " x " + foo', $c, 'bar x bar']
        ];
    }

    public static function notExpressions(): array
    {
        $c = new Context();
        return [
            // Not one is false
            ['!1', $c, false],
            // Not an empty string is true
            ['!""', $c, true],
            // Some whitespace allowed
            ['!0', $c, true],
            // A not can be a word
            ['not 0', $c, true],
        ];
    }

    public static function comparisonExpressions(): array
    {
        $c = new Context([
            'answer' => 42
        ]);
        return [
            ['1==0', $c, false],
            ['1==1', $c, true],
            ['0 == 0', $c, true],
            // It's strict
            ['0==""', $c, false],
            // Quoting doesn't matter
            ['"Foo"==\'Foo\'', $c, true],
            // Whitespace okay!
            ['1> 0', $c, true],
            // Whitespace okay!
            ['1 <0', $c, false],
            // Parenthesed comparisons
            ['(0 > 1) < (0 < 1)', $c, true],
            // Comparisons and variables
            ['answer > 1', $c, true],
            ['answer==  42', $c, true],
            // Less than equal and greater than equal
            ['1<= 0', $c, false],
            ['1 >=1', $c, true],
            // Inequality
            ['1!=1', $c, false],
            ['1!=true', $c, true],
            ['answer != 7', $c, true],
        ];
    }

    public static function calculationExpressions(): array
    {
        $c = new Context([
            'answer' => 42,
            'deeply' => [
                'nested' => [
                    'value' => 2
                ]
            ]
        ]);
        return [
            // Very basic
            ['1 + 1', $c, 2],
            ['1 - 1', $c, 0],
            ['2*2', $c, 4],
            // Multiple calc with precedence
            ['1 + 2 * 3 + 4 / 2 + 2', $c, 11],
            ['(1 + 2) * 3 + 4 / (2 + 2)', $c, 10],
            // Calculation with variables
            ['2* answer', $c, 84],
            // Calculation with nested context
            ['deeply.nested.value - 1', $c, 1],
        ];
    }

    public static function combinedExpressions(): array
    {
        $c = new Context();
        return [
            // Calculations before comparisons
            ['1 + 2 > 3', $c, false],
            // Calculations before comparisons
            ['2 * 1 == 3 - 1', $c, true],
            // Comparison on left side work too
            ['1 < 1 + 1', $c, true],
        ];
    }

    public static function booleanExpressions(): array
    {
        $c = new Context([
            'trueVar' => true,
            'falseVar' => false
        ]);
        return [
            // Boolean literals work
            ['false', $c, false],
            ['true', $c, true],
            // Conjunction before Disjunction
            ['true && true || false && false', $c, true],
            ['true && false || false && true', $c, false],
            ['1 < 2 && 2 > 1', $c, true],
            ['!1 < 2', $c, true],
            ['!(1 < 2)', $c, false],
            // Named and symbolic operators can be mixed
            ['true && true and false or false', $c, false],
            // Using variables and literals
            ['trueVar || false', $c, true],
            ['trueVar && true', $c, true],
            ['falseVar || false', $c, false],
            ['falseVar && true', $c, false],
            // JavaScript semantics of boolean operators
            ['null || "foo"', $c, 'foo'],
            ['0 || "foo"', $c, 'foo'],
            ['0 || ""', $c, ''],
            ['"bar" || "foo"', $c, 'bar'],
            ['"foo" && "bar"', $c, 'bar'],
            ['"" && false', $c, ''],
            ['"Bar" && 0', $c, 0],
            ['0 && ""', $c, 0],
        ];
    }

    public static function objectPathOnArrayExpressions(): array
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
        return [
            // Undefined variables are NULL with the default context
            ['unknwn', $c, null],
            // Simple variable statement
            ['foo', $c, 42],
            // Simple object path
            ['bar.baz', $c, 'Hello'],
            // Dynamic array like access of properties by another object path (awesome!!!)
            ['bar.a1[another.path]', $c, 'Nested'],
            // Offset access with invalid path is NULL
            ['bar.a1[unknwn.path]', $c, null],
            // Offset access with integers
            ['numeric[1]', $c, 'b'],
            ['numeric[0]', $c, 'a'],
        ];
    }

    public static function objectPathOnObjectExpressions(): array
    {
        $obj = new Fixtures\TestObject();
        $obj->setProperty('Test');
        $nested = new Fixtures\TestObject();
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
                return ['a' => 'a1', 'b' => 'b1'];
            },

            'arr' => ['a' => 1, 'b' => 2, 'c' => 3],
            'someVariable' => 'b'
        ];
        $c = new Context($contextArray);

        $protectedContext = new \Neos\Eel\ProtectedContext($contextArray);
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

    public static function arrayLiteralExpressions(): array
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
        return [
            // Empty array
            ['[]', $c, []],
            // Simple array with integer literals
            ['[1, 2, 3]', $c, [1, 2, 3]],
            // Nested array literals
            ['[[1, 2], 3, 4]', $c, [[1, 2], 3, 4]],
            // Nested expressions in array literal
            ['[[foo[bar], 2], test("a"), 4]', $c, [['Hello', 2], 'test|a|', 4]],
            // Simple array, padded with whitespace
            ['[ 1, 2, 3 ]', $c, [1, 2, 3]],
            // Simple multiline array
            ['[
                1,
                2,
                3
            ]', $c, [1, 2, 3]],
        ];
    }

    public static function objectLiteralExpressions(): array
    {
        $c = new Context([
        ]);
        return [
            // Empty object
            ['{}', $c, []],
            // Simple object literal with unquoted key
            ['{foo: "bar", bar: "baz"}', $c, ['foo' => 'bar', 'bar' => 'baz']],
            // Simple object literal with differently quoted keys
            ['{"foo": "bar", \'bar\': "baz"}', $c, ['foo' => 'bar', 'bar' => 'baz']],
            // Nested object literals with unquoted key
            ['{foo: "bar", bar: {baz: "quux"}}', $c, ['foo' => 'bar', 'bar' => ['baz' => 'quux']]],
            // Simple object literal, padded with whitespace
            ['{ foo: "bar", bar: "baz" }', $c, ['foo' => 'bar', 'bar' => 'baz']],
            // Simple multiline object literal
            ['{
                foo: "bar",
                bar: "baz"
            }', $c, ['foo' => 'bar', 'bar' => 'baz']],
        ];
    }

    public static function conditionalOperatorExpressions(): array
    {
        $c = new Context([
            'answer' => 42,
            'trueVar' => true,
            'a' => 5,
            'b' => 10
        ]);
        return [
            // Simple ternary operator expression (condition)
            ['true ? 1 : 2', $c, 1],
            // Ternary operator using variables
            ['trueVar ? answer : false', $c, 42],
            ['!trueVar ? false : answer', $c, 42],
            ['a < b ? 1 : 2', $c, 1],
            // Ternary operator with nested expressions
            ['a < b ? 1 + a : 2 + b', $c, 6],
            ['a > b ? 1 + a : 2 + b', $c, 12],
        ];
    }

    /**
     * @test
     * @dataProvider integerLiterals
     */
    public function integerLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider floatLiterals
     */
    public function floatLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider stringLiterals
     */
    public function stringLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider stringConcatenations
     */
    public function stringConcatenationsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider notExpressions
     */
    public function notExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider comparisonExpressions
     */
    public function comparisonExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider calculationExpressions
     */
    public function calculationExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider combinedExpressions
     */
    public function combinedExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider objectPathOnArrayExpressions
     */
    public function objectPathOnArrayExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider objectPathOnObjectExpressions
     */
    public function objectPathOnObjectExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider methodCallExpressions
     */
    public function methodCallExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function methodCallOfUnknownMethodThrowsException(): void
    {
        $this->expectException(EvaluationException::class);
        $o = new TestObject();

        $c = new Context([
            'context' => $o
        ]);
        $this->assertEvaluated(null, 'context.callYou("title")', $c);
    }

    /**
     * @test
     * @dataProvider booleanExpressions
     */
    public function booleanExpressionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider arrayLiteralExpressions
     */
    public function arrayLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider objectLiteralExpressions
     */
    public function objectLiteralsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @test
     * @dataProvider conditionalOperatorExpressions
     */
    public function conditionalOperatorsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    public static function invalidExpressions(): array
    {
        return [
            // Completely insane expression
            ['NULL ---invalid---'],
            // Wrong parens
            ['a * (5 + a))'],
            ['(a * 5 + b'],
            // Incomplete object path
            ['a.b. < 1'],
            // Invalid quoted strings
            ['"a "super\" \'thing\'"'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidExpressions
     */
    public function invalidExpressionsThrowExceptions(string $expression): void
    {
        $this->expectException(ParserException::class);
        $this->assertEvaluated(false, $expression, new Context());
    }

    /**
     * @test
     */
    public function expressionStartingWithWhitespaceWorkAsExpected(): void
    {
        $context = new Context(['variable' => 1]);
        $this->assertEvaluated(1, ' variable', $context);
    }

    /**
     * @test
     */
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
        self::assertSame(1, preg_match(\Neos\Eel\Package::EelExpressionRecognizer, $wrappedExpression), 'The wrapped expression ' . $wrappedExpression . ' was not detected as Eel expression');
    }

    /**
     * @return EelEvaluatorInterface
     */
    abstract protected function createEvaluator();
}
