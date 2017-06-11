<?php
namespace TYPO3\Flow\Tests\Unit\Session\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Aop\Builder\ClassNameIndex;
use TYPO3\Flow\Object\CompileTimeObjectManager;
use TYPO3\Flow\Object\Configuration\Configuration;
use TYPO3\Flow\Session\Aspect\SessionObjectMethodsPointcutFilter;
use TYPO3\Flow\Tests\UnitTestCase;

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
