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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Compiling evaluator test
 */
class CompilingEvaluatorTest extends AbstractEvaluatorTest
{
    public static function arrowFunctionExpressions(): array
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
     */
    public function arrowFunctionsCanBeParsed(string $expression, Context $context, mixed $result): void
    {
        $this->assertEvaluated($result, $expression, $context);
    }

    /**
     * @return CompilingEvaluator
     */
    protected function createEvaluator(): CompilingEvaluator
    {
        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->onlyMethods(['get'])->disableOriginalConstructor()->getMock();
        $stringFrontendMock->method('get')->willReturn(false);

        $evaluator = new CompilingEvaluator();
        $evaluator->injectExpressionCache($stringFrontendMock);
        return $evaluator;
    }

    /**
     * @test
     */
    public function doubleQuotedStringLiteralVariablesAreEscaped(): void
    {
        $context = new Context('hidden');
        $this->assertEvaluated('some {$context->unwrap()} string with \'quoted stuff\'', '"some {$context->unwrap()} string with \'quoted stuff\'"', $context);
    }

    /**
     * Assert that the expression is evaluated to the expected result
     * under the given context. It also ensures that the Eel expression is
     * recognized using the predefined regular expression.
     */
    protected function assertEvaluated(mixed $expected, string $expression, Context $context): void
    {
        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->onlyMethods(['get', 'set'])->disableOriginalConstructor()->getMock();
        $stringFrontendMock->method('get')->willReturn(false);

        /** @var CompilingEvaluator|MockObject $evaluator */
        $evaluator = $this->getAccessibleMock(CompilingEvaluator::class, []);
        $evaluator->injectExpressionCache($stringFrontendMock);
        // note, this is not a public method. We should expect expressions coming in here to be trimmed already.
        $code = $evaluator->_call('generateEvaluatorCode', trim($expression));
        self::assertSame($expected, $evaluator->evaluate($expression, $context), 'Code ' . $code . ' should evaluate to expected result');

        $wrappedExpression = '${' . $expression . '}';
        self::assertSame(1, preg_match(\Neos\Eel\Package::EelExpressionRecognizer, $wrappedExpression), 'The wrapped expression ' . $wrappedExpression . ' was not detected as Eel expression');
    }
}
