<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\Context;
use TYPO3\Eel\EelEvaluatorInterface;

/**
 * Abstract evaluator test
 *
 * Is used to test both the compiling and interpreting Eel evaluators.
 */
abstract class AbstractEvaluatorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @return array
	 */
	public function integerLiterals() {
		$c = new Context();
		return array(
			// So simple, so true
			array('1', $c, 1),
			// It all starts with zero
			array('0', $c, 0),
			// Very large number!
			array('2147483600', $c, 2147483600),
			// Don't be so negative
			array('-100', $c, -100),
		);
	}

	/**
	 * @return array
	 */
	public function floatLiterals() {
		$c = new Context();
		return array(
			array('1.0', $c, 1.0),
			array('3.141', $c, 3.141),
			array('-17.4', $c, -17.4),
		);
	}

	/**
	 * @return array
	 */
	public function stringLiterals() {
		$c = new Context();
		return array(
			// An empty string
			array('""', $c, ''),
			// Very basic
			array('"Hello world"', $c, 'Hello world'),
			// Escape not possible
			array('"Foo \"Bar\""', $c, 'Foo "Bar"'),
			// Single quotes ftw
			array('\'\'', $c, ''),
			// Single quotes ftw
			array('\'Foo\'', $c, 'Foo'),
			// Mixed quote salad
			array('\'"Foo" Bar\'', $c, '"Foo" Bar'),
		);
	}

	/**
	 * @return array
	 */
	public function stringConcatenations() {
		$c = new Context(array('foo' => 'bar'));
		return array(
			// Just concatenate two strings
			array('"a" + "b"', $c, 'ab'),
			// Concatenate a string and an integer
			array('2 + "b"', $c, '2b'),
			// Concatenate a wrapped element and a string
			array('foo + "b"', $c, 'barb'),
			// Concatenate three elements
			array('foo + " x " + foo', $c, 'bar x bar')
		);
	}

	/**
	 * @return array
	 */
	public function notExpressions() {
		$c = new Context();
		return array(
			// Not one is false
			array('!1', $c, FALSE),
			// Not an empty string is true
			array('!""', $c, TRUE),
			// Some whitespace allowed
			array('!0', $c, TRUE),
			// A not can be a word
			array('not 0', $c, TRUE),
		);
	}

	/**
	 * @return array
	 */
	public function comparisonExpressions() {
		$c = new Context(array(
			'answer' => 42
		));
		return array(
			array('1==0', $c, FALSE),
			array('1==1', $c, TRUE),
			array('0 == 0', $c, TRUE),
			// It's strict
			array('0==""', $c, FALSE),
			// Quoting doesn't matter
			array('"Foo"==\'Foo\'', $c, TRUE),
			// Whitespace okay!
			array('1> 0', $c, TRUE),
			// Whitespace okay!
			array('1 <0', $c, FALSE),
			// Parenthesed comparisons
			array('(0 > 1) < (0 < 1)', $c, TRUE),
			// Comparisons and variables
			array('answer > 1', $c, TRUE),
			array('answer==  42', $c, TRUE),
			// Less than equal and greater than equal
			array('1<= 0', $c, FALSE),
			array('1 >=1', $c, TRUE),
			// Inequality
			array('1!=1', $c, FALSE),
			array('1!=true', $c, TRUE),
			array('answer != 7', $c, TRUE),
		);
	}

	/**
	 * @return array
	 */
	public function calculationExpressions() {
		$c = new Context(array(
			'answer' => 42,
			'deeply' => array(
				'nested' => array(
					'value' => 2
				)
			)
		));
		return array(
			// Very basic
			array('1 + 1', $c, 2),
			array('1 - 1', $c, 0),
			array('2*2', $c, 4),
			// Multiple calc with precedence
			array('1 + 2 * 3 + 4 / 2 + 2', $c, 11),
			array('(1 + 2) * 3 + 4 / (2 + 2)', $c, 10),
			// Calculation with variables
			array('2* answer', $c, 84),
			// Calculation with nested context
			array('deeply.nested.value - 1', $c, 1),
		);
	}

	/**
	 * @return array
	 */
	public function combinedExpressions() {
		$c = new Context();
		return array(
			// Calculations before comparisons
			array('1 + 2 > 3', $c, FALSE),
			// Calculations before comparisons
			array('2 * 1 == 3 - 1', $c, TRUE),
			// Comparison on left side work too
			array('1 < 1 + 1', $c, TRUE),
		);
	}

	/**
	 * @return array
	 */
	public function booleanExpressions() {
		$c = new Context(array(
			'trueVar' => TRUE,
			'falseVar' => FALSE
		));
		return array(
			// Boolean literals work
			array('false', $c, FALSE),
			array('TRUE', $c, TRUE),
			// Conjunction before Disjunction
			array('TRUE && TRUE || FALSE && FALSE', $c, TRUE),
			array('TRUE && FALSE || FALSE && TRUE', $c, FALSE),
			array('1 < 2 && 2 > 1', $c, TRUE),
			array('!1 < 2', $c, TRUE),
			array('!(1 < 2)', $c, FALSE),
			// Named and symbolic operators can be mixed
			array('TRUE && true and FALSE or false', $c, FALSE),
			// Using variables and literals
			array('trueVar || FALSE', $c, TRUE),
			array('trueVar && TRUE', $c, TRUE),
			array('falseVar || FALSE', $c, FALSE),
			array('falseVar && TRUE', $c, FALSE),
			// JavaScript semantics of boolean operators
			array('null || "foo"', $c, 'foo'),
			array('0 || "foo"', $c, 'foo'),
			array('0 || ""', $c, ''),
			array('"bar" || "foo"', $c, 'bar'),
			array('"foo" && "bar"', $c, 'bar'),
			array('"" && false', $c, ''),
			array('"Bar" && 0', $c, 0),
			array('0 && ""', $c, 0),
		);
	}

	/**
	 * @return array
	 */
	public function objectPathOnArrayExpressions() {
		// Wrap a value inside a context
		$c = new Context(array(
			'foo' => 42,
			'bar' => array(
				'baz' => 'Hello',
				'a1' => array(
					'b2' => 'Nested'
				)
			),
			'another' => array(
				'path' => 'b2'
			),
			'numeric' => array('a', 'b', 'c')
		));
		return array(
			// Undefined variables are NULL with the default context
			array('unknwn', $c, NULL),
			// Simple variable statement
			array('foo', $c, 42),
			// Simple object path
			array('bar.baz', $c, 'Hello'),
			// Dynamic array like access of properties by another object path (awesome!!!)
			array('bar.a1[another.path]', $c, 'Nested'),
			// Offset access with invalid path is NULL
			array('bar.a1[unknwn.path]', $c, NULL),
			// Offset access with integers
			array('numeric[1]', $c, 'b'),
			array('numeric[0]', $c, 'a'),
		);
	}

	/**
	 * @return array
	 */
	public function objectPathOnObjectExpressions() {
		$obj = new Fixtures\TestObject();
		$obj->setProperty('Test');
		$nested = new Fixtures\TestObject();
		$nested->setProperty($obj);
		// Wrap an object inside a context
		$c = new Context(array(
			'obj' => $obj,
			'nested' => $nested
		));
		return array(
			// Access object properties by getter
			array('obj.property', $c, 'Test'),
			// Access nested objects
			array('nested.property.property', $c, 'Test'),
			// Call a method on an object
			array('obj.callMe("Foo")', $c, 'Hello, Foo!'),
		);
	}

	/**
	 * @return array
	 */
	public function methodCallExpressions() {
		// Wrap an array with functions inside a context
		$contextArray = array(
			'count' => function($array) {
				return count($array);
			},
			'pow' => function($base, $exp) {
				return pow($base, $exp);
			},
			'funcs' => array(
				'dup' => function($array) {
					return array_map(function($item) { return $item * 2; }, $array);
				}
			),
			'foo' => function() {
				return array('a' => 'a1', 'b' => 'b1');
			},

			'arr' => array('a' => 1, 'b' => 2, 'c' => 3),
			'someVariable' => 'b'
		);
		$c = new Context($contextArray);

		$protectedContext = new \TYPO3\Eel\ProtectedContext($contextArray);
		$protectedContext->whitelist('*');
		return array(
			// Call first-level method
			array('count(arr)', $c, 3),
			// Method with multiple arguments
			array('pow(2, 8)', $c, 256),
			// Combine method call and operation
			array('count(arr) + 1', $c, 4),
			// Nested method call and operation inside an method call
			array('pow(2, count(arr) + 1)', $c, 16),
			// Nest method calls and object paths
			array('funcs.dup(arr).b', $c, 4),

			// Nest method calls and array access
			array('funcs.dup(arr)[someVariable]', $c, 4),
			array('foo()[someVariable]', $c, 'b1'),
			// Nest method calls and array access with protected context
			array('foo()[someVariable]', $protectedContext, 'b1'),
			// Method call on NULL value returns NULL
			array('unknwn.func()', $c, NULL),
		);
	}

	/**
	 * @return array
	 */
	public function arrayLiteralExpressions() {
		$c = new Context(array(
			'test' => function($string) {
				return 'test|' . $string . '|';
			},
			'foo' => array(
				'baz' => 'Hello'
			),
			'bar' => 'baz'
		));
		return array(
			// Empty array
			array('[]', $c, array()),
			// Simple array with integer literals
			array('[1, 2, 3]', $c, array(1, 2, 3)),
			// Nested array literals
			array('[[1, 2], 3, 4]', $c, array(array(1, 2), 3, 4)),
			// Nested expressions in array literal
			array('[[foo[bar], 2], test("a"), 4]', $c, array(array('Hello', 2), 'test|a|', 4)),
		);
	}

	/**
	 * @return array
	 */
	public function objectLiteralExpressions() {
		$c = new Context(array(
		));
		return array(
			// Empty object
			array('{}', $c, array()),
			// Simple object literal with unquoted key
			array('{foo: "bar", bar: "baz"}', $c, array('foo' => 'bar', 'bar' => 'baz')),
			// Simple object literal with differently quoted keys
			array('{"foo": "bar", \'bar\': "baz"}', $c, array('foo' => 'bar', 'bar' => 'baz')),
			// Nested object literals with unquoted key
			array('{foo: "bar", bar: {baz: "quux"}}', $c, array('foo' => 'bar', 'bar' => array('baz' => 'quux'))),
		);
	}

	/**
	 * @return array
	 */
	public function conditionalOperatorExpressions() {
		$c = new Context(array(
			'answer' => 42,
			'trueVar' => TRUE,
			'a' => 5,
			'b' => 10
		));
		return array(
			// Simple ternary operator expression (condition)
			array('TRUE ? 1 : 2', $c, 1),
			// Ternary operator using variables
			array('trueVar ? answer : FALSE', $c, 42),
			array('!trueVar ? FALSE : answer', $c, 42),
			array('a < b ? 1 : 2', $c, 1),
			// Ternary operator with nested expressions
			array('a < b ? 1 + a : 2 + b', $c, 6),
			array('a > b ? 1 + a : 2 + b', $c, 12),
		);
	}

	/**
	 * @test
	 * @dataProvider integerLiterals
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function integerLiteralsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider floatLiterals
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function floatLiteralsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider stringLiterals
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function stringLiteralsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider stringConcatenations
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function stringConcatenationsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider notExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function notExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider comparisonExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function comparisonExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider calculationExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function calculationExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider combinedExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function combinedExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider objectPathOnArrayExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function objectPathOnArrayExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider objectPathOnObjectExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function objectPathOnObjectExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider methodCallExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function methodCallExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Eel\EvaluationException
	 */
	public function methodCallOfUndefinedFunctionThrowsException() {
		$c = new Context(array(
			'arr' => array(
				'func' => function($arg) {
					return 42;
				}
			)
		));
		$this->assertEvaluated(NULL, 'arr.funk("title")', $c);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Eel\EvaluationException
	 */
	public function methodCallOfUnknownMethodThrowsException() {
		$o = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();

		$c = new Context(array(
			'context' => $o
		));
		$this->assertEvaluated(NULL, 'context.callYou("title")', $c);
	}

	/**
	 * @test
	 * @dataProvider booleanExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function booleanExpressionsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider arrayLiteralExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function arrayLiteralsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider objectLiteralExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function objectLiteralsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @test
	 * @dataProvider conditionalOperatorExpressions
	 *
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 * @param mixed $result
	 */
	public function conditionalOperatorsCanBeParsed($expression, $context, $result) {
		$this->assertEvaluated($result, $expression, $context);
	}

	/**
	 * @return array
	 */
	public function invalidExpressions() {
		return array(
			// Completely insane expression
			array('NULL ---invalid---'),
			// Wrong parens
			array('a * (5 + a))'),
			array('(a * 5 + b'),
			// Incomplete object path
			array('a.b. < 1'),
			// Invalid quoted strings
			array('"a "super\" \'thing\'"'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidExpressions
	 *
	 * @expectedException TYPO3\Eel\ParserException
	 */
	public function invalidExpressionsThrowExceptions($expression) {
		$this->assertEvaluated(FALSE, $expression, new Context());
	}

	/**
	 * @test
	 */
	public function expressionStartingWithWhitespaceWorkAsExpected() {
		$context = new Context(array('variable' => 1));
		$this->assertEvaluated(1, ' variable', $context);
	}

	/**
	 * @test
	 */
	public function expressionEndingWithWhitespaceWorkAsExpected() {
		$context = new Context(array('variable' => 1));
		$this->assertEvaluated(1, 'variable ', $context);
	}

	/**
	 * Assert that the expression is evaluated to the expected result
	 * under the given context. It also ensures that the Eel expression is
	 * recognized using the predefined regular expression.
	 *
	 * @param mixed $expected
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 */
	protected function assertEvaluated($expected, $expression, $context) {
		$evaluator = $this->createEvaluator();
		$this->assertSame($expected, $evaluator->evaluate($expression, $context));

		$wrappedExpression = '${' . $expression . '}';
		$this->assertSame(1, preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $wrappedExpression), 'The wrapped expression ' . $wrappedExpression . ' was not detected as Eel expression');
	}

	/**
	 * @return EelEvaluatorInterface
	 */
	abstract protected function createEvaluator();

}
