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

use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Aop;

/**
 * Testcase for the Pointcut Class-Annotated-With Filter
 */
class PointcutClassAnnotatedWithFilterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class, ['getClassAnnotations'], [], '', false, true);
        $mockReflectionService->expects($this->any())->method('getClassAnnotations')->with('Acme\Some\Class', 'Acme\Some\Annotation')->will($this->onConsecutiveCalls(['SomeAnnotation'], []));

        $filter = new Aop\Pointcut\PointcutClassAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        $this->assertTrue($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
        $this->assertFalse($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotHavingTheGivenAnnotation()
    {
        $availableClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Class2',
            'TestPackage\Subpackage\SubSubPackage\Class3',
            'TestPackage\Subpackage2\Class4'
        ];
        sort($availableClassNames);
        $availableClassNamesIndex = new Aop\Builder\ClassNameIndex();
        $availableClassNamesIndex->setClassNames($availableClassNames);

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassNamesByAnnotation')->with('SomeAnnotationClass')->will($this->returnValue(['TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass']));

        $classAnnotatedWithFilter = new Aop\Pointcut\PointcutClassAnnotatedWithFilter('SomeAnnotationClass');
        $classAnnotatedWithFilter->injectReflectionService($mockReflectionService);

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $result = $classAnnotatedWithFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
