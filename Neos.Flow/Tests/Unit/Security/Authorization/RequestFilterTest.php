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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security;

/**
 * Testcase for request filters
 */
class RequestFilterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function theSetIncerceptorIsCalledIfTheRequestPatternMatches()
    {
        $request = $this->createMock(ActionRequest::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects(self::once())->method('matchRequest')->will(self::returnValue(true));
        $interceptor->expects(self::once())->method('invoke');

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    /**
     * @test
     */
    public function theSetIncerceptorIsNotCalledIfTheRequestPatternDoesNotMatch()
    {
        $request = $this->createMock(ActionRequest::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects(self::once())->method('matchRequest')->will(self::returnValue(false));
        $interceptor->expects(self::never())->method('invoke');

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    /**
     * @test
     */
    public function theFilterReturnsTrueIfThePatternMatched()
    {
        $request = $this->createMock(ActionRequest::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects(self::once())->method('matchRequest')->will(self::returnValue(true));

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        self::assertTrue($requestFilter->filterRequest($request));
    }

    /**
     * @test
     */
    public function theFilterReturnsFalseIfThePatternDidNotMatch()
    {
        $request = $this->createMock(ActionRequest::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects(self::once())->method('matchRequest')->will(self::returnValue(false));

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        self::assertFalse($requestFilter->filterRequest($request));
    }
}
