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
 * Testcase for the Pointcut Method-Annotated-With Filter
 *
 */
class PointcutMethodAnnotatedWithFilterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation()
    {
        $mockReflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class, array('getMethodAnnotations'), array(), '', false, true);
        $mockReflectionService->expects($this->any())->method('getMethodAnnotations')->with(__CLASS__, __FUNCTION__, 'Acme\Some\Annotation')->will($this->onConsecutiveCalls(array('SomeAnnotation'), array()));

        $filter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        $this->assertTrue($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
        $this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
    }

    /**
     * @test
     */
    public function matchesReturnsFalseIfMethodDoesNotExistOrDeclardingClassHasNotBeenSpecified()
    {
        $mockReflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class, array(), array(), '', false, true);

        $filter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        $this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, null, 1234));
        $this->assertFalse($filter->matches(__CLASS__, 'foo', __CLASS__, 1234));
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotHavingAMethodWithTheGivenAnnotation()
    {
        $availableClassNames = array(
            'TestPackage\Subpackage\Class1',
            'TestPackage\Class2',
            'TestPackage\Subpackage\SubSubPackage\Class3',
            'TestPackage\Subpackage2\Class4'
        );
        sort($availableClassNames);
        $availableClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $availableClassNamesIndex->setClassNames($availableClassNames);

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassesContainingMethodsAnnotatedWith')->with('SomeAnnotationClass')->will($this->returnValue(array('TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass')));

        $methodAnnotatedWithFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodAnnotatedWithFilter('SomeAnnotationClass');
        $methodAnnotatedWithFilter->injectReflectionService($mockReflectionService);

        $expectedClassNames = array(
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        );
        sort($expectedClassNames);
        $expectedClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $result = $methodAnnotatedWithFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
