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

use Neos\Cache\Frontend\StringFrontend;
use Neos\Eel\Context;
use Neos\Eel\CompilingEvaluator;

/**
 * Compiling evaluator test
 */
class CompilingEvaluatorTest extends AbstractEvaluatorTest
{

    /**
     * @return array
     */
    public function arrowFunctionExpressions()
    {
        $c = new Context([
            'items' => [1, 2, 3, 4],
            'map' => function (iterable $array, callable $callable) {
                foreach ($array as $key => $value) {
                    $array[$key] = $callable($value);
                }
                return $array;
            },
            'mapWithIndex' => function (iterable $array, callable $callable) {
                foreach ($array as $key => $value) {
                    $array[$key] = $callable($value, $key);
                }
                return $array;
            }
        ]);
        return [
            // Arrow function without parentheses
            ['map(items, x => x * x)', $c, [1, 4, 9, 16]],
            // Arrow function with parentheses
            ['map(items, (x) => x * x)', $c, [1, 4, 9, 16]],
            ['mapWithIndex(items, (v, k) => k * v)', $c, [0, 2, 6, 12]],
        ];
    }

    /**
     * @test
     * @dataProvider arrowFunctionExpressions
     *
     * @param string $expression
     * @param Context $context
     * @param mixed $result
     */
    public function arrowFunctionsCanBeParsed($expression, $context, $result)
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @return CompilingEvaluator
     */
    protected function createEvaluator()
    {
        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->setMethods([])->disableOriginalConstructor()->getMock();
        $stringFrontendMock->expects(self::any())->method('get')->willReturn(false);

        $evaluator = new CompilingEvaluator();
        $evaluator->injectExpressionCache($stringFrontendMock);
        return $evaluator;
    }

    /**
     * @test
     */
    public function doubleQuotedStringLiteralVariablesAreEscaped()
    {
        $context = new Context('hidden');
        $this->assertEvaluated('some {$context->unwrap()} string with \'quoted stuff\'', '"some {$context->unwrap()} string with \'quoted stuff\'"', $context);
    }

    /**
     * Assert that the expression is evaluated to the expected result
     * under the given context. It also ensures that the Eel expression is
     * recognized using the predefined regular expression.
     *
     * @param mixed $expected
     * @param string $expression
     * @param Context $context
     */
    protected function assertEvaluated($expected, $expression, $context)
    {
        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->setMethods([])->disableOriginalConstructor()->getMock();
        $stringFrontendMock->expects(self::any())->method('get')->willReturn(false);

        $evaluator = $this->getAccessibleMock(CompilingEvaluator::class, ['dummy']);
        $evaluator->injectExpressionCache($stringFrontendMock);
        // note, this is not a public method. We should expect expressions coming in here to be trimmed already.
        $code = $evaluator->_call('generateEvaluatorCode', trim($expression));
        self::assertSame($expected, $evaluator->evaluate($expression, $context), 'Code ' . $code . ' should evaluate to expected result');

        $wrappedExpression = '${' . $expression . '}';
        self::assertSame(1, preg_match(\Neos\Eel\Package::EelExpressionRecognizer, $wrappedExpression), 'The wrapped expression ' . $wrappedExpression . ' was not detected as Eel expression');
    }
}
