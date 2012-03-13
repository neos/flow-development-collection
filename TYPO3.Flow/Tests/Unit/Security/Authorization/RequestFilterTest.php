<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
class RequestFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theSetIncerceptorIsCalledIfTheRequestPatternMatches() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('TYPO3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));
		$interceptor->expects($this->once())->method('invoke');

		$requestFilter = new \TYPO3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 */
	public function theSetIncerceptorIsNotCalledIfTheRequestPatternDoesNotMatch() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('TYPO3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));
		$interceptor->expects($this->never())->method('invoke');

		$requestFilter = new \TYPO3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 */
	public function theSetIncerceptorIsNotCalledIfTheRequestPatternCannotMatchTheRequest() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('TYPO3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(FALSE));
		$requestPattern->expects($this->never())->method('matchRequest');
		$interceptor->expects($this->never())->method('invoke');

		$requestFilter = new \TYPO3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 */
	public function theFilterReturnsTrueIfThePatternMatched() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('TYPO3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));

		$requestFilter = new \TYPO3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$this->assertTrue($requestFilter->filterRequest($request));
	}

	/**
	 * @test
	 */
	public function theFilterReturnsFalseIfThePatternDidNotMatch() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('TYPO3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));

		$requestFilter = new \TYPO3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$this->assertFalse($requestFilter->filterRequest($request));
	}
}
?>