<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Eel\Context;
use TYPO3\Eel\InterpretedEvaluator;

/**
 * A benchmark to test the interpreting evaluator
 *
 * @group benchmark
 */
class InterpretedEvaluatorBenchmarkTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function loopedExpressions()
    {
        $this->markTestSkipped('Enable for benchmark');

        $evaluator = new InterpretedEvaluator();
        $expression = 'foo.bar=="Test"||foo.baz=="Test"||reverse(foo).bar=="Test"';
        $context = new Context(array(
            'foo' => array(
                'bar' => 'Test1',
                'baz' => 'Test2'
            ),
            'reverse' => function ($array) {
                return array_reverse($array, true);
            }
        ));
        for ($i = 0; $i < 10000; $i++) {
            $evaluator->evaluate($expression, $context);
        }
    }
}
