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

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\ProtectedContext;
use Neos\Eel\Tests\Unit\Fixtures\TestObject;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Untrusted context test
 */
class ProtectedContextTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\Eel\NotAllowedException
     */
    public function methodCallToAnyValueIsNotAllowed()
    {
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'secure' => $securedObject
        ]);

        $evaluator = new CompilingEvaluator();
        $evaluator->evaluate('secure.callMe("Christopher")', $context);
    }

    /**
     * @test
     * @expectedException \Neos\Eel\NotAllowedException
     */
    public function arrayAccessResultIsStillUntrusted()
    {
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'secure' => [$securedObject]
        ]);

        $evaluator = new CompilingEvaluator();
        $evaluator->evaluate('secure[0].callMe("Christopher")', $context);
    }

    /**
     * @test
     */
    public function propertyAccessToAnyValueIsAllowed()
    {
        $object = (object)[
            'foo' => 'Bar'
        ];

        $context = new ProtectedContext([
            'value' => $object
        ]);

        $evaluator = new CompilingEvaluator();
        $result = $evaluator->evaluate('value.foo', $context);

        $this->assertEquals('Bar', $result);
    }

    /**
     * @test
     */
    public function methodCallToWhitelistedValueIsAllowed()
    {
        $context = new ProtectedContext([
            'String' => new \Neos\Eel\Helper\StringHelper()
        ]);
        $context->whitelist('String.*');

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('String.substr("Hello World", 6, 5)', $context);

        $this->assertEquals('World', $result);
    }

    /**
     * @test
     * @expectedException \Neos\Eel\NotAllowedException
     */
    public function firstLevelFunctionsHaveToBeWhitelisted()
    {
        $context = new ProtectedContext([
            'ident' => function ($value) {
                return $value;
            }
        ]);

        $evaluator = new CompilingEvaluator();

        $evaluator->evaluate('ident(42)', $context);
    }

    /**
     * @test
     * @expectedException \Neos\Eel\NotAllowedException
     */
    public function resultOfFirstLevelMethodCallIsProtected()
    {
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'ident' => function ($value) {
                return $value;
            },
            'value' => $securedObject
        ]);
        $context->whitelist(['ident']);

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('ident(value)', $context);
        $this->assertEquals($securedObject, $result);

        $evaluator->evaluate('ident(value).callMe("Foo")', $context);
    }

    /**
     * @test
     * @expectedException \Neos\Eel\NotAllowedException
     */
    public function resultOfWhitelistedMethodCallIsProtected()
    {
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'Array' => [
                'reverse' => function ($value) {
                    return array_reverse($value);
                }
            ],
            'value' => [$securedObject]
        ]);
        $context->whitelist('Array');

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('Array.reverse(value)[0]', $context);
        $this->assertEquals($securedObject, $result);

        $evaluator->evaluate('Array.reverse(value)[0].callMe("Foo")', $context);
    }

    /**
     * @test
     */
    public function chainedCallsArePossibleWithExplicitContextWrapping()
    {
        $context = new ProtectedContext([
            // Simulate something like FlowQuery
            'q' => function ($value) {
                $context = new ProtectedContext(['count' => function () use ($value) {
                    return count($value);
                }]);
                $context->whitelist('*');
                return $context;
            },
            'value' => ['Foo', 'Bar']
        ]);
        $context->whitelist('q');

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('q(value).count()', $context);
        $this->assertEquals(2, $result);
    }

    /**
     * @test
     */
    public function protectedContextAwareInterfaceAllowsCallsDynamicallyWithoutWhitelist()
    {
        $securedObject = new TestObject();

        $securedObject->setDynamicMethodName('callMe');

        $context = new ProtectedContext([
            'value' => $securedObject
        ]);

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('value.callMe("Foo")', $context);
        $this->assertEquals('Hello, Foo!', $result);
    }

    /**
     * @test
     */
    public function methodCallToNullValueDoesNotThrowNotAllowedException()
    {
        $context = new ProtectedContext([

        ]);

        $evaluator = new CompilingEvaluator();
        $result = $evaluator->evaluate('unknown.someMethod()', $context);
        $this->assertEquals(null, $result);
    }
}
