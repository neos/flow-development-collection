<?php
namespace Neos\Flow\Tests\Unit\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Aop;

/**
 * Testcase for the Pointcut Filter
 */
class PointcutFilterTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\UnknownPointcutException
     */
    public function matchesThrowsAnExceptionIfTheSpecifiedPointcutDoesNotExist()
    {
        $className = 'Foo';
        $methodName = 'bar';
        $methodDeclaringClassName = 'Baz';
        $pointcutQueryIdentifier = 42;

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(false));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
    }

    /**
     * @test
     */
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName()
    {
        $className = 'Foo';
        $methodName = 'bar';
        $methodDeclaringClassName = 'Baz';
        $pointcutQueryIdentifier = 42;

        $mockPointcut = $this->getMockBuilder(Aop\Pointcut\Pointcut::class)->disableOriginalConstructor()->setMethods(['matches'])->getMock();
        $mockPointcut->expects($this->once())->method('matches')->with($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)->will($this->returnValue('the result'));

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $this->assertSame('the result', $pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier));
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsDefinitionReturnsTheDefinitionArrayFromThePointcut()
    {
        $mockPointcut = $this->getMockBuilder(Aop\Pointcut\Pointcut::class)->disableOriginalConstructor()->getMock();
        $mockPointcut->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(['evaluations']));

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $this->assertEquals(['evaluations'], $pointcutFilter->getRuntimeEvaluationsDefinition(), 'Something different from an array was returned.');
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsDefinitionReturnsAnEmptyArrayIfThePointcutDoesNotExist()
    {
        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(false));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $this->assertEquals([], $pointcutFilter->getRuntimeEvaluationsDefinition(), 'The definition array has not been returned as exptected.');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesAsksTheResolvedPointcutToReduce()
    {
        $mockPointcut = $this->getMockBuilder(Aop\Pointcut\Pointcut::class)->disableOriginalConstructor()->getMock();
        $mockPointcut->expects($this->once())->method('reduceTargetClassNames')->will($this->returnValue('someResult'));

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        $this->assertEquals('someResult', $pointcutFilter->reduceTargetClassNames(new Aop\Builder\ClassNameIndex()));
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesReturnsTheInputClassNameIndexIfThePointcutCouldNotBeResolved()
    {
        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(false));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        $inputClassNameIndex = new Aop\Builder\ClassNameIndex();

        $this->assertSame($inputClassNameIndex, $pointcutFilter->reduceTargetClassNames($inputClassNameIndex));
    }
}
