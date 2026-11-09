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
use Neos\Flow\Aop\Exception\CircularPointcutReferenceException;
use Neos\Flow\Aop\Pointcut;
use Neos\Flow\Aop\Pointcut\PointcutFilterComposite;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the default AOP Pointcut implementation
 */
final class PointcutTest extends UnitTestCase
{
    #[Test]
    public function matchesChecksIfTheGivenClassAndMethodMatchThePointcutFilterComposite()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';
        $methodName = 'TheMethod';

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->onlyMethods(['matches'])->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('matches')->with($className, $methodName, $className, 1)->willReturn((true));

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->onlyMethods([])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        self::assertTrue($pointcut->matches($className, $methodName, $className, 1));
    }

    #[Test]
    public function matchesDetectsCircularMatchesAndThrowsAndException()
    {
        $this->expectException(CircularPointcutReferenceException::class);
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';
        $methodName = 'TheMethod';

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->onlyMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->onlyMethods([])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        for ($i = -1; $i <= Pointcut\Pointcut::MAXIMUM_RECURSIONS; $i++) {
            $pointcut->matches($className, $methodName, $className, 1);
        }
    }

    #[Test]
    public function getPointcutExpressionReturnsThePointcutExpression()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->onlyMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->onlyMethods([])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        self::assertSame($pointcutExpression, $pointcut->getPointcutExpression());
    }

    #[Test]
    public function getAspectClassNameReturnsTheAspectClassName()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->onlyMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->onlyMethods([])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        self::assertSame($aspectClassName, $pointcut->getAspectClassName());
    }

    #[Test]
    public function getPointcutMethodNameReturnsThePointcutMethodName()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->onlyMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->onlyMethods([])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, 'PointcutMethod'])->getMock();
        self::assertSame('PointcutMethod', $pointcut->getPointcutMethodName());
    }

    #[Test]
    public function getRuntimeEvaluationsReturnsTheRuntimeEvaluationsDefinitionOfTheContainedPointcutFilterComposite()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';

        $mockPointcutFilterComposite = $this->createMock(PointcutFilterComposite::class);
        $mockPointcutFilterComposite->expects($this->once())->method('getRuntimeEvaluationsDefinition')->willReturn((['runtimeEvaluationsDefinition']));

        $pointcut = new Pointcut\Pointcut($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, $className);

        self::assertSame(['runtimeEvaluationsDefinition'], $pointcut->getRuntimeEvaluationsDefinition(), 'The runtime evaluations definition has not been returned as expected.');
    }

    #[Test]
    public function reduceTargetClassNamesAsksThePointcutsFilterCompositeToReduce()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';
        $resultClassNameIndex = new ClassNameIndex();

        $targetClassNameIndex = new ClassNameIndex();

        $mockPointcutFilterComposite = $this->createMock(PointcutFilterComposite::class);
        $mockPointcutFilterComposite->expects($this->once())->method('reduceTargetClassNames')->with($targetClassNameIndex)->willReturn($resultClassNameIndex);

        $pointcut = new Pointcut\Pointcut($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, $className);

        self::assertEquals($resultClassNameIndex, $pointcut->reduceTargetClassNames($targetClassNameIndex));
    }
}
