<?php

declare(strict_types=1);

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
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Builder\ProxyClassBuilder;
use Neos\Flow\Aop\Exception\UnknownPointcutException;
use Neos\Flow\Aop\Pointcut\Pointcut;
use Neos\Flow\Aop\Pointcut\PointcutFilter;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Pointcut Filter
 */
final class PointcutFilterTest extends UnitTestCase
{
    #[Test]
    public function matchesThrowsAnExceptionIfTheSpecifiedPointcutDoesNotExist()
    {
        $this->expectException(UnknownPointcutException::class);
        $className = 'Foo';
        $methodName = 'bar';
        $methodDeclaringClassName = 'Baz';
        $pointcutQueryIdentifier = 42;

        $mockProxyClassBuilder = $this->getMockBuilder(ProxyClassBuilder::class)->disableOriginalConstructor()->onlyMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->willReturn((false));

        $pointcutFilter = new PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
    }

    #[Test]
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName()
    {
        $className = 'Foo';
        $methodName = 'bar';
        $methodDeclaringClassName = 'Baz';
        $pointcutQueryIdentifier = 42;

        $mockPointcut = $this->getMockBuilder(Pointcut::class)->disableOriginalConstructor()->onlyMethods(['matches'])->getMock();
        $mockPointcut->expects($this->once())->method('matches')->with($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)->willReturn(true);

        $mockProxyClassBuilder = $this->getMockBuilder(ProxyClassBuilder::class)->disableOriginalConstructor()->onlyMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->willReturn(($mockPointcut));

        $pointcutFilter = new PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        self::assertTrue($pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier));
    }

    #[Test]
    public function getRuntimeEvaluationsDefinitionReturnsTheDefinitionArrayFromThePointcut()
    {
        $mockPointcut = $this->createMock(Pointcut::class);
        $mockPointcut->expects($this->once())->method('getRuntimeEvaluationsDefinition')->willReturn((['evaluations']));

        $mockProxyClassBuilder = $this->getMockBuilder(ProxyClassBuilder::class)->disableOriginalConstructor()->onlyMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->willReturn(($mockPointcut));

        $pointcutFilter = new PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        self::assertSame(['evaluations'], $pointcutFilter->getRuntimeEvaluationsDefinition(), 'Something different from an array was returned.');
    }

    #[Test]
    public function getRuntimeEvaluationsDefinitionReturnsAnEmptyArrayIfThePointcutDoesNotExist()
    {
        $mockProxyClassBuilder = $this->getMockBuilder(ProxyClassBuilder::class)->disableOriginalConstructor()->onlyMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->willReturn((false));

        $pointcutFilter = new PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        self::assertSame([], $pointcutFilter->getRuntimeEvaluationsDefinition(), 'The definition array has not been returned as exptected.');
    }

    #[Test]
    public function reduceTargetClassNamesAsksTheResolvedPointcutToReduce()
    {
        $resultClassNameIndex = new ClassNameIndex();
        $mockPointcut = $this->createMock(Pointcut::class);
        $mockPointcut->expects($this->once())->method('reduceTargetClassNames')->willReturn($resultClassNameIndex);

        $mockProxyClassBuilder = $this->getMockBuilder(ProxyClassBuilder::class)->disableOriginalConstructor()->onlyMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->willReturn(($mockPointcut));

        $pointcutFilter = new PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        self::assertEquals($resultClassNameIndex, $pointcutFilter->reduceTargetClassNames(new ClassNameIndex()));
    }

    #[Test]
    public function reduceTargetClassNamesReturnsTheInputClassNameIndexIfThePointcutCouldNotBeResolved()
    {
        $mockProxyClassBuilder = $this->getMockBuilder(ProxyClassBuilder::class)->disableOriginalConstructor()->onlyMethods(['findPointcut'])->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->willReturn((false));

        $pointcutFilter = new PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        $inputClassNameIndex = new ClassNameIndex();

        self::assertSame($inputClassNameIndex, $pointcutFilter->reduceTargetClassNames($inputClassNameIndex));
    }
}
