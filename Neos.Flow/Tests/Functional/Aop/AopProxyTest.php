<?php
namespace Neos\Flow\Tests\Functional\Aop;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Test suite for aop proxy classes
 */
class AopProxyTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function advicesAreExecutedAgainIfAnOverriddenMethodCallsItsParentMethod()
    {
        $targetClass = new Fixtures\ChildClassOfTargetClass01();
        $this->assertEquals('Greetings, I just wanted to say: Hello World World', $targetClass->sayHello());
    }

    /**
     * @test
     */
    public function anAdvicedParentMethodIsCalledCorrectlyIfANonAdvicedOverridingMethodCallsIt()
    {
        $targetClass = new Fixtures\ChildClassOfTargetClass01();
        $this->assertEquals('Two plus two makes five! For big twos and small fives! That was smart, eh?', $targetClass->saySomethingSmart());
    }

    /**
     * @test
     */
    public function methodArgumentsWithValueNullArePassedToTheProxiedMethod()
    {
        $proxiedClass = new Fixtures\EntityWithOptionalConstructorArguments('argument1', null, 'argument3');

        $this->assertEquals('argument1', $proxiedClass->argument1);
        $this->assertNull($proxiedClass->argument2);
        $this->assertEquals('argument3', $proxiedClass->argument3);
    }

    /**
     * @test
     */
    public function staticMethodsCannotBeAdvised()
    {
        $targetClass01 = new Fixtures\TargetClass01();
        $this->assertSame('I won\'t take any advice', $targetClass01->someStaticMethod());
    }

    /**
     * @test
     */
    public function canCallAdvicedParentMethodNotDeclaredInChild()
    {
        $targetClass = new Fixtures\ChildClassOfTargetClass01();
        $greeting = $targetClass->greet('Flow');
        $this->assertEquals('Hello, me', $greeting);
    }

    /**
     * @test
     */
    public function cloneCanCallParentCloneMethod()
    {
        $entity = new Fixtures\PrototypeClassGsubsub();
        $this->assertSame('real', $entity->realOrCloned);
        $clone = clone $entity;
        $this->assertSame('cloned!', $clone->realOrCloned);
    }
}
