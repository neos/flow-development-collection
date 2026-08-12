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
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Aop\Pointcut\PointcutClassAnnotatedWithFilter;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Pointcut Class-Annotated-With Filter
 */
final class PointcutClassAnnotatedWithFilterTest extends UnitTestCase
{
    #[Test]
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class, ['getClassAnnotations'], [], '', false, true);
        $mockReflectionService->method('getClassAnnotations')->with('Acme\Some\Class', 'Acme\Some\Annotation')->willReturnOnConsecutiveCalls(['SomeAnnotation'], []);

        $filter = new PointcutClassAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        self::assertTrue($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
        self::assertFalse($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
    }

    #[Test]
    public function reduceTargetClassNamesFiltersAllClassesNotHavingTheGivenAnnotation()
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

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('getClassNamesByAnnotation')->with('SomeAnnotationClass')->willReturn((['TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass']));

        $classAnnotatedWithFilter = new PointcutClassAnnotatedWithFilter('SomeAnnotationClass');
        $classAnnotatedWithFilter->injectReflectionService($mockReflectionService);

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $result = $classAnnotatedWithFilter->reduceTargetClassNames($availableClassNamesIndex);

        self::assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
