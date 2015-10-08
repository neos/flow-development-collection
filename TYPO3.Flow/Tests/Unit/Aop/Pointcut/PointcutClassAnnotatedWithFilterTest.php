<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Pointcut;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the Pointcut Class-Annotated-With Filter
 *
 */
class PointcutClassAnnotatedWithFilterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation()
    {
        $mockReflectionService = $this->getMock(\TYPO3\Flow\Reflection\ReflectionService::class, array('getClassAnnotations'), array(), '', false, true);
        $mockReflectionService->expects($this->any())->method('getClassAnnotations')->with('Acme\Some\Class', 'Acme\Some\Annotation')->will($this->onConsecutiveCalls(array('SomeAnnotation'), array()));

        $filter = new \TYPO3\Flow\Aop\Pointcut\PointcutClassAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        $this->assertTrue($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
        $this->assertFalse($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotHavingTheGivenAnnotation()
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

        $mockReflectionService = $this->getMock(\TYPO3\Flow\Reflection\ReflectionService::class, array(), array(), '', false);
        $mockReflectionService->expects($this->any())->method('getClassNamesByAnnotation')->with('SomeAnnotationClass')->will($this->returnValue(array('TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass')));

        $classAnnotatedWithFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutClassAnnotatedWithFilter('SomeAnnotationClass');
        $classAnnotatedWithFilter->injectReflectionService($mockReflectionService);

        $expectedClassNames = array(
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        );
        sort($expectedClassNames);
        $expectedClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $result = $classAnnotatedWithFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
