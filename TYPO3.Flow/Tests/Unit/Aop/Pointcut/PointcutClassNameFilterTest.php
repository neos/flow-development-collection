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

require_once(FLOW_PATH_FLOW . 'Tests/Unit/Fixtures/DummyClass.php');
require_once(FLOW_PATH_FLOW . 'Tests/Unit/Fixtures/SecondDummyClass.php');

/**
 * Testcase for the Pointcut Class Filter
 *
 */
class PointcutClassNameFilterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Checks if the class filter fires on a concrete and simple class expression
     *
     * @test
     */
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName()
    {
        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();

        $classFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutClassNameFilter('TYPO3\Virtual\Foo\Bar');
        $classFilter->injectReflectionService($mockReflectionService);
        $this->assertTrue($classFilter->matches('TYPO3\Virtual\Foo\Bar', '', '', 1), 'No. 1');

        $classFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutClassNameFilter('.*Virtual.*');
        $classFilter->injectReflectionService($mockReflectionService);
        $this->assertTrue($classFilter->matches('TYPO3\Virtual\Foo\Bar', '', '', 1), 'No. 2');

        $classFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutClassNameFilter('TYPO3\Firtual.*');
        $classFilter->injectReflectionService($mockReflectionService);
        $this->assertFalse($classFilter->matches('TYPO3\Virtual\Foo\Bar', '', '', 1), 'No. 3');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotMatchedByAClassNameFilter()
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

        $expectedClassNames = array(
            'TestPackage\Subpackage\SubSubPackage\Class3'
        );
        sort($expectedClassNames);
        $expectedClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $classNameFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\SubSubPackage\Class3');
        $result = $classNameFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotMatchedByAClassNameFilterWithRegularExpressions()
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

        $expectedClassNames = array(
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        );
        sort($expectedClassNames);
        $expectedClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $classNameFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\.*');
        $result = $classNameFilter->reduceTargetClassNames($availableClassNamesIndex);

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
