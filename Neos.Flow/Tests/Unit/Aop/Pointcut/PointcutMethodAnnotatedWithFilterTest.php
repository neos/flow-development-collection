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
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Pointcut\PointcutMethodAnnotatedWithFilter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Pointcut Method-Annotated-With Filter
 */
final class PointcutMethodAnnotatedWithFilterTest extends UnitTestCase
{
    #[Test]
    public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation()
    {
        $mockReflectionService = $this->createMock(ReflectionService::class, ['getMethodAnnotations'], [], '', false, true);
        $mockReflectionService->method('getMethodAnnotations')->with(__CLASS__, __FUNCTION__, 'Acme\Some\Annotation')->willReturnOnConsecutiveCalls(['SomeAnnotation'], []);

        $filter = new PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        self::assertTrue($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
        self::assertFalse($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
    }

    #[Test]
    public function matchesReturnsFalseIfMethodDoesNotExistOrDeclardingClassHasNotBeenSpecified()
    {
        $mockReflectionService = $this->createStub(ReflectionService::class, [], [], '', false, true);

        $filter = new PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
        $filter->injectReflectionService($mockReflectionService);

        self::assertFalse($filter->matches(__CLASS__, __FUNCTION__, null, 1234));
        self::assertFalse($filter->matches(__CLASS__, 'foo', __CLASS__, 1234));
    }

    #[Test]
    public function reduceTargetClassNamesFiltersAllClassesNotHavingAMethodWithTheGivenAnnotation()
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
        $mockReflectionService->method('getClassesContainingMethodsAnnotatedWith')->with('SomeAnnotationClass')->willReturn((['TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass']));

        $methodAnnotatedWithFilter = new PointcutMethodAnnotatedWithFilter('SomeAnnotationClass');
        $methodAnnotatedWithFilter->injectReflectionService($mockReflectionService);

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $result = $methodAnnotatedWithFilter->reduceTargetClassNames($availableClassNamesIndex);

        self::assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
