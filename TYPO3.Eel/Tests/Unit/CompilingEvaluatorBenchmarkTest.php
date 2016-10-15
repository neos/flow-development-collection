<?php
namespace TYPO3\Eel\Tests\Unit;

/*
 * This file is part of the TYPO3.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\Context;
use TYPO3\Eel\CompilingEvaluator;

/**
 * A benchmark to test the compiling evaluator
 *
 * @group benchmark
 */
class CompilingEvaluatorBenchmarkTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function loopedExpressions()
    {
        $this->markTestSkipped('Enable for benchmark');

        $evaluator = new CompilingEvaluator();
        $expression = 'foo.bar=="Test"||foo.baz=="Test"||reverse(foo).bar=="Test"';
        $context = new Context([
            'foo' => [
                'bar' => 'Test1',
                'baz' => 'Test2'
            ],
            'reverse' => function ($array) {
                return array_reverse($array, true);
            }
        ]);
        for ($i = 0; $i < 10000; $i++) {
            $evaluator->evaluate($expression, $context);
        }
    }
}
