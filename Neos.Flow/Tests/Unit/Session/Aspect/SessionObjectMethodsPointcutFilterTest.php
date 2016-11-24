<?php
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

/**
 * Testcase for the SessionObjectMethodsPointcutFilter
 */
class SessionObjectMethodsPointcutFilterTest extends UnitTestCase
{
    /**
     * @test
     */
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

        $mockCompileTimeObjectManager = $this->getMockBuilder(CompileTimeObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockCompileTimeObjectManager->expects($this->any())->method('getClassNamesByScope')->with(Configuration::SCOPE_SESSION)->will($this->returnValue(['TestPackage\Subpackage\Class1', 'TestPackage\Subpackage\SubSubPackage\Class3', 'SomeMoreClass']));

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

        $this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}
