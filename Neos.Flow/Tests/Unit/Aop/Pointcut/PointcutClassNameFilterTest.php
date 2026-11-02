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

require_once(FLOW_PATH_FLOW . 'Tests/Unit/Fixtures/DummyClass.php');
require_once(FLOW_PATH_FLOW . 'Tests/Unit/Fixtures/SecondDummyClass.php');

use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Pointcut\PointcutClassNameFilter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Pointcut Class Filter
 */
final class PointcutClassNameFilterTest extends UnitTestCase
{
    /**
     * Checks if the class filter fires on a concrete and simple class expression
     */
    #[Test]
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName()
    {
        $mockReflectionService = $this->createStub(ReflectionService::class);

        $classFilter = new PointcutClassNameFilter('Neos\Virtual\Foo\Bar');
        $classFilter->injectReflectionService($mockReflectionService);
        self::assertTrue($classFilter->matches('Neos\Virtual\Foo\Bar', '', '', 1), 'No. 1');

        $classFilter = new PointcutClassNameFilter('.*Virtual.*');
        $classFilter->injectReflectionService($mockReflectionService);
        self::assertTrue($classFilter->matches('Neos\Virtual\Foo\Bar', '', '', 1), 'No. 2');

        $classFilter = new PointcutClassNameFilter('Neos\Firtual.*');
        $classFilter->injectReflectionService($mockReflectionService);
        self::assertFalse($classFilter->matches('Neos\Virtual\Foo\Bar', '', '', 1), 'No. 3');
    }

    #[Test]
    public function reduceTargetClassNamesFiltersAllClassesNotMatchedByAClassNameFilter()
    {
        $availableClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Class2',
            'TestPackage\Subpackage\SubSubPackage\Class3',
            'TestPackage\Subpackage2\Class4'
        ];
        sort($availableClassNames);
        $availableClassNamesIndex = new ClassNameIndex();
        $availableClassNamesIndex->setClassNames($availableClassNames);

        $expectedClassNames = [
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $classNameFilter = new PointcutClassNameFilter('TestPackage\Subpackage\SubSubPackage\Class3');
        $result = $classNameFilter->reduceTargetClassNames($availableClassNamesIndex);

        self::assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }

    #[Test]
    public function reduceTargetClassNamesFiltersAllClassesNotMatchedByAClassNameFilterWithRegularExpressions()
    {
        $availableClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Class2',
            'TestPackage\Subpackage\SubSubPackage\Class3',
            'TestPackage\Subpackage2\Class4'
        ];
        sort($availableClassNames);
        $availableClassNamesIndex = new ClassNameIndex();
        $availableClassNamesIndex->setClassNames($availableClassNames);

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $classNameFilter = new PointcutClassNameFilter('TestPackage\Subpackage\.*');
        $result = $classNameFilter->reduceTargetClassNames($availableClassNamesIndex);

        self::assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
