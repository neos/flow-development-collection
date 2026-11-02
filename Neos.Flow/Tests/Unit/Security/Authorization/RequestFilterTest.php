<?php

declare(strict_types=1);

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authorization\InterceptorInterface;
use Neos\Flow\Security\Authorization\RequestFilter;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for request filters
 */
final class RequestFilterTest extends UnitTestCase
{
    #[Test]
    public function theSetIncerceptorIsCalledIfTheRequestPatternMatches()
    {
        $request = $this->createStub(ActionRequest::class);
        $requestPattern = $this->createMock(RequestPatternInterface::class);
        $interceptor = $this->createMock(InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->willReturn((true));
        $interceptor->expects($this->once())->method('invoke');

        $requestFilter = new RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    #[Test]
    public function theSetIncerceptorIsNotCalledIfTheRequestPatternDoesNotMatch()
    {
        $request = $this->createStub(ActionRequest::class);
        $requestPattern = $this->createMock(RequestPatternInterface::class);
        $interceptor = $this->createMock(InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->willReturn((false));
        $interceptor->expects($this->never())->method('invoke');

        $requestFilter = new RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    #[Test]
    public function theFilterReturnsTrueIfThePatternMatched()
    {
        $request = $this->createStub(ActionRequest::class);
        $requestPattern = $this->createMock(RequestPatternInterface::class);
        $interceptor = $this->createStub(InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->willReturn((true));

        $requestFilter = new RequestFilter($requestPattern, $interceptor);
        self::assertTrue($requestFilter->filterRequest($request));
    }

    #[Test]
    public function theFilterReturnsFalseIfThePatternDidNotMatch()
    {
        $request = $this->createStub(ActionRequest::class);
        $requestPattern = $this->createMock(RequestPatternInterface::class);
        $interceptor = $this->createStub(InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->willReturn((false));

        $requestFilter = new RequestFilter($requestPattern, $interceptor);
        self::assertFalse($requestFilter->filterRequest($request));
    }
}
