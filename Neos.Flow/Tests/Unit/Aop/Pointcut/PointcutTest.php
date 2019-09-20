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
use Neos\Flow\Aop\Pointcut;
use Neos\Flow\Aop;

/**
 * Testcase for the default AOP Pointcut implementation
 */
class PointcutTest extends UnitTestCase
{
    /**
     * @test
     */
    public function matchesChecksIfTheGivenClassAndMethodMatchThePointcutFilterComposite()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';
        $methodName = 'TheMethod';

        $mockPointcutFilterComposite = $this->getMockBuilder(Pointcut\PointcutFilterComposite::class)->disableOriginalConstructor()->setMethods(['matches'])->getMock();
        $mockPointcutFilterComposite->expects(self::once())->method('matches')->with($className, $methodName, $className, 1)->will(self::returnValue(true));

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->setMethods(['dummy'])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        self::assertTrue($pointcut->matches($className, $methodName, $className, 1));
    }

    /**
     * @test
     */
    public function matchesDetectsCircularMatchesAndThrowsAndException()
    {
        $this->expectException(Aop\Exception\CircularPointcutReferenceException::class);
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';
        $methodName = 'TheMethod';

        $mockPointcutFilterComposite = $this->getMockBuilder(Pointcut\PointcutFilterComposite::class)->disableOriginalConstructor()->setMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->setMethods(['dummy'])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        for ($i = -1; $i <= Pointcut\Pointcut::MAXIMUM_RECURSIONS; $i++) {
            $pointcut->matches($className, $methodName, $className, 1);
        }
    }

    /**
     * @test
     */
    public function getPointcutExpressionReturnsThePointcutExpression()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';

        $mockPointcutFilterComposite = $this->getMockBuilder(Pointcut\PointcutFilterComposite::class)->disableOriginalConstructor()->setMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->setMethods(['dummy'])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        self::assertSame($pointcutExpression, $pointcut->getPointcutExpression());
    }

    /**
     * @test
     */
    public function getAspectClassNameReturnsTheAspectClassName()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';

        $mockPointcutFilterComposite = $this->getMockBuilder(Pointcut\PointcutFilterComposite::class)->disableOriginalConstructor()->setMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->setMethods(['dummy'])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName])->getMock();
        self::assertSame($aspectClassName, $pointcut->getAspectClassName());
    }

    /**
     * @test
     */
    public function getPointcutMethodNameReturnsThePointcutMethodName()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';

        $mockPointcutFilterComposite = $this->getMockBuilder(Pointcut\PointcutFilterComposite::class)->disableOriginalConstructor()->setMethods(['matches'])->getMock();

        $pointcut = $this->getMockBuilder(Pointcut\Pointcut::class)->setMethods(['dummy'])->setConstructorArgs([$pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, 'PointcutMethod'])->getMock();
        self::assertSame('PointcutMethod', $pointcut->getPointcutMethodName());
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsReturnsTheRuntimeEvaluationsDefinitionOfTheContainedPointcutFilterComposite()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';

        $mockPointcutFilterComposite = $this->getMockBuilder(Pointcut\PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects(self::once())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['runtimeEvaluationsDefinition']));

        $pointcut = new Pointcut\Pointcut($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, $className);

        self::assertEquals(['runtimeEvaluationsDefinition'], $pointcut->getRuntimeEvaluationsDefinition(), 'The runtime evaluations definition has not been returned as expected.');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesAsksThePointcutsFilterCompositeToReduce()
    {
        $pointcutExpression = 'ThePointcutExpression';
        $aspectClassName = 'TheAspect';
        $className = 'TheClass';
        $resultClassNameIndex = new Aop\Builder\ClassNameIndex();

        $targetClassNameIndex = new Aop\Builder\ClassNameIndex();

        $mockPointcutFilterComposite = $this->getMockBuilder(Pointcut\PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects(self::once())->method('reduceTargetClassNames')->with($targetClassNameIndex)->willReturn($resultClassNameIndex);

        $pointcut = new Pointcut\Pointcut($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, $className);

        self::assertEquals($resultClassNameIndex, $pointcut->reduceTargetClassNames($targetClassNameIndex));
    }
}
