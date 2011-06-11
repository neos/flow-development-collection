<?php
namespace F3\FLOW3\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for request filters
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestFilterTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theSetIncerceptorIsCalledIfTheRequestPatternMatches() {
		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('F3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));
		$interceptor->expects($this->once())->method('invoke');

		$requestFilter = new \F3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theSetIncerceptorIsNotCalledIfTheRequestPatternDoesNotMatch() {
		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('F3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));
		$interceptor->expects($this->never())->method('invoke');

		$requestFilter = new \F3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theSetIncerceptorIsNotCalledIfTheRequestPatternCannotMatchTheRequest() {
		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('F3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(FALSE));
		$requestPattern->expects($this->never())->method('matchRequest');
		$interceptor->expects($this->never())->method('invoke');

		$requestFilter = new \F3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$requestFilter->filterRequest($request);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theFilterReturnsTrueIfThePatternMatched() {
		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('F3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));

		$requestFilter = new \F3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$this->assertTrue($requestFilter->filterRequest($request));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theFilterReturnsFalseIfThePatternDidNotMatch() {
		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$requestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface');
		$interceptor = $this->getMock('F3\FLOW3\Security\Authorization\InterceptorInterface');

		$requestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$requestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));

		$requestFilter = new \F3\FLOW3\Security\Authorization\RequestFilter($requestPattern, $interceptor);
		$this->assertFalse($requestFilter->filterRequest($request));
	}
}
?>