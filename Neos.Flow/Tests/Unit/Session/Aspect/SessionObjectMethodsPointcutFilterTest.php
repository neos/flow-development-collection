<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Session\Aspect;

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
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\Session\Aspect\SessionObjectMethodsPointcutFilter;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the SessionObjectMethodsPointcutFilter
 */
final class SessionObjectMethodsPointcutFilterTest extends UnitTestCase
{
    #[Test]
    public function reduceTargetClassNamesFiltersAllClassesNotBeeingConfiguredAsScopeSession()
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

        $mockCompileTimeObjectManager = $this->createMock(CompileTimeObjectManager::class);
        $mockCompileTimeObjectManager->method('getClassNamesByScope')->with(Configuration::SCOPE_SESSION)->willReturn((['TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass']));

        $sessionObjectMethodsPointcutFilter = new SessionObjectMethodsPointcutFilter();
        $sessionObjectMethodsPointcutFilter->injectObjectManager($mockCompileTimeObjectManager);

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $result = $sessionObjectMethodsPointcutFilter->reduceTargetClassNames($availableClassNamesIndex);

        self::assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
