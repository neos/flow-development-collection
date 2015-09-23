<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the security interceptor resolver
 *
 */
class InterceptorResolverTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException TYPO3\Flow\Security\Exception\NoInterceptorFoundException
     */
    public function resolveInterceptorClassThrowsAnExceptionIfNoInterceptorIsAvailable()
    {
        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManager', array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $interceptorResolver = new \TYPO3\Flow\Security\Authorization\InterceptorResolver($mockObjectManager);

        $interceptorResolver->resolveInterceptorClass('notExistingClass');
    }

    /**
     * @test
     */
    public function resolveInterceptorReturnsTheCorrectInterceptorForAShortName()
    {
        $getCaseSensitiveObjectNameCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'TYPO3\Flow\Security\Authorization\Interceptor\ValidShortName') {
                return 'TYPO3\Flow\Security\Authorization\Interceptor\ValidShortName';
            }

            return false;
        };

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManager', array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));


        $interceptorResolver = new \TYPO3\Flow\Security\Authorization\InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ValidShortName');

        $this->assertEquals('TYPO3\Flow\Security\Authorization\Interceptor\ValidShortName', $interceptorClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveInterceptorReturnsTheCorrectInterceptorForACompleteClassName()
    {
        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManager', array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('ExistingInterceptorClass')->will($this->returnValue('ExistingInterceptorClass'));

        $interceptorResolver = new \TYPO3\Flow\Security\Authorization\InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ExistingInterceptorClass');

        $this->assertEquals('ExistingInterceptorClass', $interceptorClass, 'The wrong classname has been resolved');
    }
}
