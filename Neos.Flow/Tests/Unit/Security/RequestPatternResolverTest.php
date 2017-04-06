<?php
namespace Neos\Flow\Tests\Unit\Security;

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
use Neos\Flow\Security\RequestPatternResolver;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the request pattern resolver
 */
class RequestPatternResolverTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\NoRequestPatternFoundException
     */
    public function resolveRequestPatternClassThrowsAnExceptionIfNoRequestPatternIsAvailable()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);

        $requestPatternResolver->resolveRequestPatternClass('notExistingClass');
    }

    /**
     * @test
     */
    public function resolveRequestPatternReturnsTheCorrectRequestPatternForAShortName()
    {
        $longNameForTest = 'Neos\Flow\Security\RequestPattern\ValidShortName';

        $getCaseSensitiveObjectNameCallback = function () use ($longNameForTest) {
            $args = func_get_args();

            if ($args[0] === $longNameForTest) {
                return $longNameForTest;
            }

            return false;
        };

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ValidShortName');

        $this->assertEquals($longNameForTest, $requestPatternClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveRequestPatternReturnsTheCorrectRequestPatternForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('ExistingRequestPatternClass')->will($this->returnValue('ExistingRequestPatternClass'));

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ExistingRequestPatternClass');

        $this->assertEquals('ExistingRequestPatternClass', $requestPatternClass, 'The wrong classname has been resolved');
    }
}
