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

use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Eel\ProtectedContext;

/**
 * Untrusted context test
 */
class ProtectedContextTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Eel\NotAllowedException
     */
    public function methodCallToAnyValueIsNotAllowed()
    {
        $securedObject = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();

        $context = new ProtectedContext(array(
            'secure' => $securedObject
        ));

        $evaluator = new CompilingEvaluator();
        $evaluator->evaluate('secure.callMe("Christopher")', $context);
    }

    /**
     * @test
     * @expectedException \TYPO3\Eel\NotAllowedException
     */
    public function arrayAccessResultIsStillUntrusted()
    {
        $securedObject = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();

        $context = new ProtectedContext(array(
            'secure' => array($securedObject)
        ));

        $evaluator = new CompilingEvaluator();
        $evaluator->evaluate('secure[0].callMe("Christopher")', $context);
    }

    /**
     * @test
     */
    public function propertyAccessToAnyValueIsAllowed()
    {
        $object = (object)array(
            'foo' => 'Bar'
        );

        $context = new ProtectedContext(array(
            'value' => $object
        ));

        $evaluator = new CompilingEvaluator();
        $result = $evaluator->evaluate('value.foo', $context);

        $this->assertEquals('Bar', $result);
    }

    /**
     * @test
     */
    public function methodCallToWhitelistedValueIsAllowed()
    {
        $context = new ProtectedContext(array(
            'String' => new \TYPO3\Eel\Helper\StringHelper()
        ));
        $context->whitelist('String.*');

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('String.substr("Hello World", 6, 5)', $context);

        $this->assertEquals('World', $result);
    }

    /**
     * @test
     * @expectedException \TYPO3\Eel\NotAllowedException
     */
    public function firstLevelFunctionsHaveToBeWhitelisted()
    {
        $context = new ProtectedContext(array(
            'ident' => function ($value) {
                return $value;
            }
        ));

        $evaluator = new CompilingEvaluator();

        $evaluator->evaluate('ident(42)', $context);
    }

    /**
     * @test
     * @expectedException \TYPO3\Eel\NotAllowedException
     */
    public function resultOfFirstLevelMethodCallIsProtected()
    {
        $securedObject = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();

        $context = new ProtectedContext(array(
            'ident' => function ($value) {
                return $value;
            },
            'value' => $securedObject
        ));
        $context->whitelist(array('ident'));

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('ident(value)', $context);
        $this->assertEquals($securedObject, $result);

        $evaluator->evaluate('ident(value).callMe("Foo")', $context);
    }

    /**
     * @test
     * @expectedException \TYPO3\Eel\NotAllowedException
     */
    public function resultOfWhitelistedMethodCallIsProtected()
    {
        $securedObject = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();

        $context = new ProtectedContext(array(
            'Array' => array(
                'reverse' => function ($value) {
                    return array_reverse($value);
                }
            ),
            'value' => array($securedObject)
        ));
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
        $context = new ProtectedContext(array(
            // Simulate something like FlowQuery
            'q' => function ($value) {
                $context = new ProtectedContext(array('count' => function () use ($value) {
                    return count($value);
                }));
                $context->whitelist('*');
                return $context;
            },
            'value' => array('Foo', 'Bar')
        ));
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
        $securedObject = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();

        $securedObject->setDynamicMethodName('callMe');

        $context = new ProtectedContext(array(
            'value' => $securedObject
        ));

        $evaluator = new CompilingEvaluator();

        $result = $evaluator->evaluate('value.callMe("Foo")', $context);
        $this->assertEquals('Hello, Foo!', $result);
    }

    /**
     * @test
     */
    public function methodCallToNullValueDoesNotThrowNotAllowedException()
    {
        $context = new ProtectedContext(array(

        ));

        $evaluator = new CompilingEvaluator();
        $result = $evaluator->evaluate('unknown.someMethod()', $context);
        $this->assertEquals(null, $result);
    }
}
