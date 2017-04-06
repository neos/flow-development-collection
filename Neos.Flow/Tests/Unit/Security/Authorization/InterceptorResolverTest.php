<?php
namespace Neos\Flow\Tests\Unit\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security;

/**
 * Testcase for the security interceptor resolver
 */
class InterceptorResolverTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\NoInterceptorFoundException
     */
    public function resolveInterceptorClassThrowsAnExceptionIfNoInterceptorIsAvailable()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $interceptorResolver = new Security\Authorization\InterceptorResolver($mockObjectManager);

        $interceptorResolver->resolveInterceptorClass('notExistingClass');
    }

    /**
     * @test
     */
    public function resolveInterceptorReturnsTheCorrectInterceptorForAShortName()
    {
        $longClassNameForTest = 'Neos\Flow\Security\Authorization\Interceptor\ValidShortName';

        $getCaseSensitiveObjectNameCallback = function () use ($longClassNameForTest) {
            $args = func_get_args();

            if ($args[0] === $longClassNameForTest) {
                return $longClassNameForTest;
            }

            return false;
        };

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));


        $interceptorResolver = new Security\Authorization\InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ValidShortName');

        $this->assertEquals($longClassNameForTest, $interceptorClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveInterceptorReturnsTheCorrectInterceptorForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('ExistingInterceptorClass')->will($this->returnValue('ExistingInterceptorClass'));

        $interceptorResolver = new Security\Authorization\InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ExistingInterceptorClass');

        $this->assertEquals('ExistingInterceptorClass', $interceptorClass, 'The wrong classname has been resolved');
    }
}
