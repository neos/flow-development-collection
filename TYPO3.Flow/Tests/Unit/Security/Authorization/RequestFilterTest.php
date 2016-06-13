<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $request = $this->createMock('TYPO3\Flow\Mvc\RequestInterface');
        $requestPattern = $this->createMock('TYPO3\Flow\Security\RequestPatternInterface');
        $interceptor = $this->createMock('TYPO3\Flow\Security\Authorization\InterceptorInterface');

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
        $request = $this->createMock('TYPO3\Flow\Mvc\RequestInterface');
        $requestPattern = $this->createMock('TYPO3\Flow\Security\RequestPatternInterface');
        $interceptor = $this->createMock('TYPO3\Flow\Security\Authorization\InterceptorInterface');

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
        $request = $this->createMock('TYPO3\Flow\Mvc\RequestInterface');
        $requestPattern = $this->createMock('TYPO3\Flow\Security\RequestPatternInterface');
        $interceptor = $this->createMock('TYPO3\Flow\Security\Authorization\InterceptorInterface');

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(true));

        $requestFilter = new \TYPO3\Flow\Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $this->assertTrue($requestFilter->filterRequest($request));
    }

    /**
     * @test
     */
    public function theFilterReturnsFalseIfThePatternDidNotMatch()
    {
        $request = $this->createMock('TYPO3\Flow\Mvc\RequestInterface');
        $requestPattern = $this->createMock('TYPO3\Flow\Security\RequestPatternInterface');
        $interceptor = $this->createMock('TYPO3\Flow\Security\Authorization\InterceptorInterface');

        $requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(false));

        $requestFilter = new \TYPO3\Flow\Security\Authorization\RequestFilter($requestPattern, $interceptor);
        $this->assertFalse($requestFilter->filterRequest($request));
    }
}
