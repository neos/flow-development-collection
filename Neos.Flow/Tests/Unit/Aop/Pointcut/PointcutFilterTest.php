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
     */
    public function matchesThrowsAnExceptionIfTheSpecifiedPointcutDoesNotExist()
    {
        $this->expectException(Aop\Exception\UnknownPointcutException::class);
        $className = 'Foo';
        $methodName = 'bar';
        $methodDeclaringClassName = 'Baz';
        $pointcutQueryIdentifier = 42;

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects(self::once())->method('findPointcut')->with('Aspect', 'pointcut')->will(self::returnValue(false));

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
        $mockPointcut->expects(self::once())->method('matches')->with($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)->willReturn(true);

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects(self::once())->method('findPointcut')->with('Aspect', 'pointcut')->will(self::returnValue($mockPointcut));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        self::assertTrue($pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier));
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsDefinitionReturnsTheDefinitionArrayFromThePointcut()
    {
        $mockPointcut = $this->getMockBuilder(Aop\Pointcut\Pointcut::class)->disableOriginalConstructor()->getMock();
        $mockPointcut->expects(self::once())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['evaluations']));

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects(self::once())->method('findPointcut')->with('Aspect', 'pointcut')->will(self::returnValue($mockPointcut));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        self::assertEquals(['evaluations'], $pointcutFilter->getRuntimeEvaluationsDefinition(), 'Something different from an array was returned.');
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsDefinitionReturnsAnEmptyArrayIfThePointcutDoesNotExist()
    {
        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects(self::once())->method('findPointcut')->with('Aspect', 'pointcut')->will(self::returnValue(false));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        self::assertEquals([], $pointcutFilter->getRuntimeEvaluationsDefinition(), 'The definition array has not been returned as exptected.');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesAsksTheResolvedPointcutToReduce()
    {
        $resultClassNameIndex = new Aop\Builder\ClassNameIndex();
        $mockPointcut = $this->getMockBuilder(Aop\Pointcut\Pointcut::class)->disableOriginalConstructor()->getMock();
        $mockPointcut->expects(self::once())->method('reduceTargetClassNames')->willReturn($resultClassNameIndex);

        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects(self::once())->method('findPointcut')->with('Aspect', 'pointcut')->will(self::returnValue($mockPointcut));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        self::assertEquals($resultClassNameIndex, $pointcutFilter->reduceTargetClassNames(new Aop\Builder\ClassNameIndex()));
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesReturnsTheInputClassNameIndexIfThePointcutCouldNotBeResolved()
    {
        $mockProxyClassBuilder = $this->getMockBuilder(Aop\Builder\ProxyClassBuilder::class)->disableOriginalConstructor()->setMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects(self::once())->method('findPointcut')->with('Aspect', 'pointcut')->will(self::returnValue(false));

        $pointcutFilter = new Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        $inputClassNameIndex = new Aop\Builder\ClassNameIndex();

        self::assertSame($inputClassNameIndex, $pointcutFilter->reduceTargetClassNames($inputClassNameIndex));
    }
}
