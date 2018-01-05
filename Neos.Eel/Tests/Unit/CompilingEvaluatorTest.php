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
     * @return CompilingEvaluator
     */
    protected function createEvaluator()
    {
        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->setMethods([])->disableOriginalConstructor()->getMock();
        $stringFrontendMock->expects(self::any())->method('get')->willReturn(false);

        return new CompilingEvaluator($stringFrontendMock);
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

        $evaluator = $this->getAccessibleMock(CompilingEvaluator::class, ['dummy'], [$stringFrontendMock]);
        // note, this is not a public method. We should expect expressions coming in here to be trimmed already.
        $code = $evaluator->_call('generateEvaluatorCode', trim($expression));
        $this->assertSame($expected, $evaluator->evaluate($expression, $context), 'Code ' . $code . ' should evaluate to expected result');

        $wrappedExpression = '${' . $expression . '}';
        $this->assertSame(1, preg_match(\Neos\Eel\Package::EelExpressionRecognizer, $wrappedExpression), 'The wrapped expression ' . $wrappedExpression . ' was not detected as Eel expression');
    }
}
