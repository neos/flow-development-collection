<?php
namespace TYPO3\Flow\Tests\Unit\Security;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the request pattern resolver
 *
 */
class RequestPatternResolverTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\NoRequestPatternFoundException
     */
    public function resolveRequestPatternClassThrowsAnExceptionIfNoRequestPatternIsAvailable()
    {
        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManager::class, array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $requestPatternResolver = new \TYPO3\Flow\Security\RequestPatternResolver($mockObjectManager);

        $requestPatternResolver->resolveRequestPatternClass('notExistingClass');
    }

    /**
     * @test
     */
    public function resolveRequestPatternReturnsTheCorrectRequestPatternForAShortName()
    {
        $getCaseSensitiveObjectNameCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'TYPO3\Flow\Security\RequestPattern\ValidShortName') {
                return 'TYPO3\Flow\Security\RequestPattern\ValidShortName';
            }

            return false;
        };

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManager::class, array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));

        $requestPatternResolver = new \TYPO3\Flow\Security\RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ValidShortName');

        $this->assertEquals('TYPO3\Flow\Security\RequestPattern\ValidShortName', $requestPatternClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveRequestPatternReturnsTheCorrectRequestPatternForACompleteClassName()
    {
        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManager::class, array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('ExistingRequestPatternClass')->will($this->returnValue('ExistingRequestPatternClass'));

        $requestPatternResolver = new \TYPO3\Flow\Security\RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ExistingRequestPatternClass');

        $this->assertEquals('ExistingRequestPatternClass', $requestPatternClass, 'The wrong classname has been resolved');
    }
}
