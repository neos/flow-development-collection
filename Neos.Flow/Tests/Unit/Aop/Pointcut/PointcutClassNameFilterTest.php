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

require_once(FLOW_PATH_FLOW . 'Tests/Unit/Fixtures/DummyClass.php');
require_once(FLOW_PATH_FLOW . 'Tests/Unit/Fixtures/SecondDummyClass.php');

use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Aop;

/**
 * Testcase for the Pointcut Class Filter
 */
class PointcutClassNameFilterTest extends UnitTestCase
{
    /**
     * Checks if the class filter fires on a concrete and simple class expression
     *
     * @test
     */
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName()
    {
        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();

        $classFilter = new Aop\Pointcut\PointcutClassNameFilter('Neos\Virtual\Foo\Bar');
        $classFilter->injectReflectionService($mockReflectionService);
        $this->assertTrue($classFilter->matches('Neos\Virtual\Foo\Bar', '', '', 1), 'No. 1');

        $classFilter = new Aop\Pointcut\PointcutClassNameFilter('.*Virtual.*');
        $classFilter->injectReflectionService($mockReflectionService);
        $this->assertTrue($classFilter->matches('Neos\Virtual\Foo\Bar', '', '', 1), 'No. 2');

        $classFilter = new Aop\Pointcut\PointcutClassNameFilter('Neos\Firtual.*');
        $classFilter->injectReflectionService($mockReflectionService);
        $this->assertFalse($classFilter->matches('Neos\Virtual\Foo\Bar', '', '', 1), 'No. 3');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotMatchedByAClassNameFilter()
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

        $expectedClassNames = [
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $classNameFilter = new Aop\Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\SubSubPackage\Class3');
        $result = $classNameFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotMatchedByAClassNameFilterWithRegularExpressions()
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

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $classNameFilter = new Aop\Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\.*');
        $result = $classNameFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
