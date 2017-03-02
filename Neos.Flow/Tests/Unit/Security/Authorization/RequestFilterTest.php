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

use Neos\Flow\Mvc\RequestInterface;
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
        $request = $this->createMock(RequestInterface::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(true));
        $interceptor->expects($this->once())->method('invoke');

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    /**
     * @test
     */
    public function theSetIncerceptorIsNotCalledIfTheRequestPatternDoesNotMatch()
    {
        $request = $this->createMock(RequestInterface::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(false));
        $interceptor->expects($this->never())->method('invoke');

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    /**
     * @test
     */
    public function theFilterReturnsTrueIfThePatternMatched()
    {
        $request = $this->createMock(RequestInterface::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(true));

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $this->assertTrue($requestFilter->filterRequest($request));
    }

    /**
     * @test
     */
    public function theFilterReturnsFalseIfThePatternDidNotMatch()
    {
        $request = $this->createMock(RequestInterface::class);
        $requestPattern = $this->createMock(Security\RequestPatternInterface::class);
        $interceptor = $this->createMock(Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(false));

        $requestFilter = new Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $this->assertFalse($requestFilter->filterRequest($request));
    }
}
