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
use Neos\Flow\Security\Exception\NoRequestPatternFoundException;
use Neos\Flow\Security\RequestPatternResolver;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the request pattern resolver
 */
class RequestPatternResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveRequestPatternClassThrowsAnExceptionIfNoRequestPatternIsAvailable()
    {
        $this->expectException(NoRequestPatternFoundException::class);
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnValue(false));

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
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnCallBack($getCaseSensitiveObjectNameCallback));

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ValidShortName');

        self::assertEquals($longNameForTest, $requestPatternClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveRequestPatternReturnsTheCorrectRequestPatternForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->with('ExistingRequestPatternClass')->will(self::returnValue('ExistingRequestPatternClass'));

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ExistingRequestPatternClass');

        self::assertEquals('ExistingRequestPatternClass', $requestPatternClass, 'The wrong classname has been resolved');
    }
}
