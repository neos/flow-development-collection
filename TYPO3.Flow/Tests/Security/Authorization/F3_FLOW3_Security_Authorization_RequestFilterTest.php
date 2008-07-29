<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for request filters
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_RequestFilterTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theSetIncerceptorIsCalledIfTheRequestPatternMatches() {
		$request = $this->getMock('F3_FLOW3_MVC_Request');
		$requestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface');
		$interceptor = $this->getMock('F3_FLOW3_Security_Authorization_InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));
		$interceptor->expects($this->once())->method('invoke');

		$requestFilter = new F3_FLOW3_Security_Authorization_RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theSetIncerceptorIsNotCalledIfTheRequestPatternDoesNotMatch() {
		$request = $this->getMock('F3_FLOW3_MVC_Request');
		$requestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface');
		$interceptor = $this->getMock('F3_FLOW3_Security_Authorization_InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));
		$interceptor->expects($this->never())->method('invoke');

		$requestFilter = new F3_FLOW3_Security_Authorization_RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theSetIncerceptorIsNotCalledIfTheRequestPatternCannotMatchTheRequest() {
		$request = $this->getMock('F3_FLOW3_MVC_Request');
		$requestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface');
		$interceptor = $this->getMock('F3_FLOW3_Security_Authorization_InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(FALSE));
		$requestPattern->expects($this->never())->method('matchRequest');
		$interceptor->expects($this->never())->method('invoke');

		$requestFilter = new F3_FLOW3_Security_Authorization_RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theFilterReturnsTrueIfThePatternMatched() {
		$request = $this->getMock('F3_FLOW3_MVC_Request');
		$requestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface');
		$interceptor = $this->getMock('F3_FLOW3_Security_Authorization_InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));

		$requestFilter = new F3_FLOW3_Security_Authorization_RequestFilter($requestPattern, $interceptor);
		$this->assertTrue($requestFilter->filterRequest($request));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theFilterReturnsFalseIfThePatternDidNotMatch() {
		$request = $this->getMock('F3_FLOW3_MVC_Request');
		$requestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface');
		$interceptor = $this->getMock('F3_FLOW3_Security_Authorization_InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));

		$requestFilter = new F3_FLOW3_Security_Authorization_RequestFilter($requestPattern, $interceptor);
		$this->assertFalse($requestFilter->filterRequest($request));
	}
}
?>