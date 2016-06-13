<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Pointcut;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the Pointcut Filter
 *
 */
class PointcutFilterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Aop\Exception\UnknownPointcutException
     */
    public function matchesThrowsAnExceptionIfTheSpecifiedPointcutDoesNotExist()
    {
        $className = 'Foo';
        $methodName = 'bar';
        $methodDeclaringClassName = 'Baz';
        $pointcutQueryIdentifier = 42;

        $mockProxyClassBuilder = $this->getMockBuilder('TYPO3\Flow\Aop\Builder\ProxyClassBuilder')->disableOriginalConstructor()->setMethods(array('findPointcut'))->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(false));

        $pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
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

        $mockPointcut = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\Pointcut')->disableOriginalConstructor()->setMethods(array('matches'))->getMock();
        $mockPointcut->expects($this->once())->method('matches')->with($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)->will($this->returnValue('the result'));

        $mockProxyClassBuilder = $this->getMockBuilder('TYPO3\Flow\Aop\Builder\ProxyClassBuilder')->disableOriginalConstructor()->setMethods(array('findPointcut'))->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

        $pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $this->assertSame('the result', $pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier));
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsDefinitionReturnsTheDefinitionArrayFromThePointcut()
    {
        $mockPointcut = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\Pointcut')->disableOriginalConstructor()->getMock();
        $mockPointcut->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('evaluations')));

        $mockProxyClassBuilder = $this->getMockBuilder('TYPO3\Flow\Aop\Builder\ProxyClassBuilder')->disableOriginalConstructor()->setMethods(array('findPointcut'))->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

        $pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $this->assertEquals(array('evaluations'), $pointcutFilter->getRuntimeEvaluationsDefinition(), 'Something different from an array was returned.');
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsDefinitionReturnsAnEmptyArrayIfThePointcutDoesNotExist()
    {
        $mockProxyClassBuilder = $this->getMockBuilder('TYPO3\Flow\Aop\Builder\ProxyClassBuilder')->disableOriginalConstructor()->setMethods(array('findPointcut'))->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(false));

        $pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
        $this->assertEquals(array(), $pointcutFilter->getRuntimeEvaluationsDefinition(), 'The definition array has not been returned as exptected.');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesAsksTheResolvedPointcutToReduce()
    {
        $mockPointcut = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\Pointcut')->disableOriginalConstructor()->getMock();
        $mockPointcut->expects($this->once())->method('reduceTargetClassNames')->will($this->returnValue('someResult'));

        $mockProxyClassBuilder = $this->getMockBuilder('TYPO3\Flow\Aop\Builder\ProxyClassBuilder')->disableOriginalConstructor()->setMethods(array('findPointcut'))->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

        $pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        $this->assertEquals('someResult', $pointcutFilter->reduceTargetClassNames(new \TYPO3\Flow\Aop\Builder\ClassNameIndex()));
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesReturnsTheInputClassNameIndexIfThePointcutCouldNotBeResolved()
    {
        $mockProxyClassBuilder = $this->getMockBuilder('TYPO3\Flow\Aop\Builder\ProxyClassBuilder')->disableOriginalConstructor()->setMethods(array('findPointcut'))->getMock();
        $mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(false));

        $pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
        $pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

        $inputClassNameIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();

        $this->assertSame($inputClassNameIndex, $pointcutFilter->reduceTargetClassNames($inputClassNameIndex));
    }
}
