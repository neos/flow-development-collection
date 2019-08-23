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
     */
    public function resolveInterceptorClassThrowsAnExceptionIfNoInterceptorIsAvailable()
    {
        $this->expectException(Security\Exception\NoInterceptorFoundException::class);
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnValue(false));

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
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnCallBack($getCaseSensitiveObjectNameCallback));


        $interceptorResolver = new Security\Authorization\InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ValidShortName');

        self::assertEquals($longClassNameForTest, $interceptorClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveInterceptorReturnsTheCorrectInterceptorForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->with('ExistingInterceptorClass')->will(self::returnValue('ExistingInterceptorClass'));

        $interceptorResolver = new Security\Authorization\InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ExistingInterceptorClass');

        self::assertEquals('ExistingInterceptorClass', $interceptorClass, 'The wrong classname has been resolved');
    }
}
