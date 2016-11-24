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
 * Testcase for the Pointcut Method-Annotated-With Filter
 */
class PointcutMethodAnnotatedWithFilterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class, ['getMethodAnnotations'], [], '', false, true);
        $mockReflectionService->expects($this->any())->method('getMethodAnnotations')->with(__CLASS__, __FUNCTION__, 'Acme\Some\Annotation')->will($this->onConsecutiveCalls(['SomeAnnotation'], []));

        $filter = new Aop\Pointcut\PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        $this->assertTrue($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
        $this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
    }

    /**
     * @test
     */
    public function matchesReturnsFalseIfMethodDoesNotExistOrDeclardingClassHasNotBeenSpecified()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class, [], [], '', false, true);

        $filter = new Aop\Pointcut\PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        $this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, null, 1234));
        $this->assertFalse($filter->matches(__CLASS__, 'foo', __CLASS__, 1234));
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotHavingAMethodWithTheGivenAnnotation()
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
        $mockReflectionService->expects($this->any())->method('getClassesContainingMethodsAnnotatedWith')->with('SomeAnnotationClass')->will($this->returnValue(['TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass']));

        $methodAnnotatedWithFilter = new Aop\Pointcut\PointcutMethodAnnotatedWithFilter('SomeAnnotationClass');
        $methodAnnotatedWithFilter->injectReflectionService($mockReflectionService);

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $result = $methodAnnotatedWithFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
