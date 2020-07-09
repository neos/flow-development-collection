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
use Neos\Eel\CompilingEvaluator;
use Neos\Eel\NotAllowedException;
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
     */
    public function methodCallToAnyValueIsNotAllowed()
    {
        $this->expectException(NotAllowedException::class);
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'secure' => $securedObject
        ]);

        $evaluator = $this->createEvaluator();
        $evaluator->evaluate('secure.callMe("Christopher")', $context);
    }

    /**
     * @test
     */
    public function arrayAccessResultIsStillUntrusted()
    {
        $this->expectException(NotAllowedException::class);
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'secure' => [$securedObject]
        ]);

        $evaluator = $this->createEvaluator();
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

        $evaluator = $this->createEvaluator();
        $result = $evaluator->evaluate('value.foo', $context);

        self::assertEquals('Bar', $result);
    }

    /**
     * @test
     */
    public function methodCallToAllowedValueIsAllowed()
    {
        $context = new ProtectedContext([
            'String' => new \Neos\Eel\Helper\StringHelper()
        ]);
        $context->allow('String.*');

        $evaluator = $this->createEvaluator();

        $result = $evaluator->evaluate('String.substr("Hello World", 6, 5)', $context);

        self::assertEquals('World', $result);
    }

    /**
     * @test
     */
    public function firstLevelFunctionsHaveToBeAllowed()
    {
        $this->expectException(NotAllowedException::class);
        $context = new ProtectedContext([
            'ident' => function ($value) {
                return $value;
            }
        ]);

        $evaluator = $this->createEvaluator();

        $evaluator->evaluate('ident(42)', $context);
    }

    /**
     * @test
     */
    public function resultOfFirstLevelMethodCallIsProtected()
    {
        $this->expectException(NotAllowedException::class);
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'ident' => function ($value) {
                return $value;
            },
            'value' => $securedObject
        ]);
        $context->allow(['ident']);

        $evaluator = $this->createEvaluator();

        $result = $evaluator->evaluate('ident(value)', $context);
        self::assertEquals($securedObject, $result);

        $evaluator->evaluate('ident(value).callMe("Foo")', $context);
    }

    /**
     * @test
     */
    public function resultOfAllowedMethodCallIsProtected()
    {
        $this->expectException(NotAllowedException::class);
        $securedObject = new TestObject();

        $context = new ProtectedContext([
            'Array' => [
                'reverse' => function ($value) {
                    return array_reverse($value);
                }
            ],
            'value' => [$securedObject]
        ]);
        $context->allow('Array');

        $evaluator = $this->createEvaluator();

        $result = $evaluator->evaluate('Array.reverse(value)[0]', $context);
        self::assertEquals($securedObject, $result);

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
                $context->allow('*');
                return $context;
            },
            'value' => ['Foo', 'Bar']
        ]);
        $context->allow('q');

        $evaluator = $this->createEvaluator();

        $result = $evaluator->evaluate('q(value).count()', $context);
        self::assertEquals(2, $result);
    }

    /**
     * @test
     */
    public function protectedContextAwareInterfaceAllowsCallsDynamicallyWithoutAllowlist()
    {
        $securedObject = new TestObject();

        $securedObject->setDynamicMethodName('callMe');

        $context = new ProtectedContext([
            'value' => $securedObject
        ]);

        $evaluator = $this->createEvaluator();

        $result = $evaluator->evaluate('value.callMe("Foo")', $context);
        self::assertEquals('Hello, Foo!', $result);
    }

    /**
     * @test
     */
    public function methodCallToNullValueDoesNotThrowNotAllowedException()
    {
        $context = new ProtectedContext([

        ]);

        $evaluator = $this->createEvaluator();
        $result = $evaluator->evaluate('unknown.someMethod()', $context);
        self::assertEquals(null, $result);
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
}
