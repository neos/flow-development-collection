<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the request pattern resolver
 */
final class RequestPatternResolverTest extends UnitTestCase
{
    #[Test]
    public function resolveRequestPatternClassThrowsAnExceptionIfNoRequestPatternIsAvailable()
    {
        $this->expectException(NoRequestPatternFoundException::class);
        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->willReturn((false));

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);

        $requestPatternResolver->resolveRequestPatternClass('notExistingClass');
    }

    #[Test]
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

        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->willReturnCallback($getCaseSensitiveObjectNameCallback);

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ValidShortName');

        self::assertEquals($longNameForTest, $requestPatternClass, 'The wrong classname has been resolved');
    }

    #[Test]
    public function resolveRequestPatternReturnsTheCorrectRequestPatternForACompleteClassName()
    {
        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->with('ExistingRequestPatternClass')->willReturn(('ExistingRequestPatternClass'));

        $requestPatternResolver = new RequestPatternResolver($mockObjectManager);
        $requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('ExistingRequestPatternClass');

        self::assertEquals('ExistingRequestPatternClass', $requestPatternClass, 'The wrong classname has been resolved');
    }
}
