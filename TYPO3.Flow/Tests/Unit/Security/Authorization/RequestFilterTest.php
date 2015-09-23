<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for request filters
 */
class RequestFilterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function theSetIncerceptorIsCalledIfTheRequestPatternMatches()
    {
        $request = $this->getMock(\TYPO3\Flow\Mvc\RequestInterface::class);
        $requestPattern = $this->getMock(\TYPO3\Flow\Security\RequestPatternInterface::class);
        $interceptor = $this->getMock(\TYPO3\Flow\Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(true));
        $interceptor->expects($this->once())->method('invoke');

        $requestFilter = new \TYPO3\Flow\Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    /**
     * @test
     */
    public function theSetIncerceptorIsNotCalledIfTheRequestPatternDoesNotMatch()
    {
        $request = $this->getMock(\TYPO3\Flow\Mvc\RequestInterface::class);
        $requestPattern = $this->getMock(\TYPO3\Flow\Security\RequestPatternInterface::class);
        $interceptor = $this->getMock(\TYPO3\Flow\Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(false));
        $interceptor->expects($this->never())->method('invoke');

        $requestFilter = new \TYPO3\Flow\Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $requestFilter->filterRequest($request);
    }

    /**
     * @test
     */
    public function theFilterReturnsTrueIfThePatternMatched()
    {
        $request = $this->getMock(\TYPO3\Flow\Mvc\RequestInterface::class);
        $requestPattern = $this->getMock(\TYPO3\Flow\Security\RequestPatternInterface::class);
        $interceptor = $this->getMock(\TYPO3\Flow\Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(true));

        $requestFilter = new \TYPO3\Flow\Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $this->assertTrue($requestFilter->filterRequest($request));
    }

    /**
     * @test
     */
    public function theFilterReturnsFalseIfThePatternDidNotMatch()
    {
        $request = $this->getMock(\TYPO3\Flow\Mvc\RequestInterface::class);
        $requestPattern = $this->getMock(\TYPO3\Flow\Security\RequestPatternInterface::class);
        $interceptor = $this->getMock(\TYPO3\Flow\Security\Authorization\InterceptorInterface::class);

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(false));

        $requestFilter = new \TYPO3\Flow\Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $this->assertFalse($requestFilter->filterRequest($request));
    }
}
