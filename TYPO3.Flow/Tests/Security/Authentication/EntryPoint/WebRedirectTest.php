<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\EntryPoint;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Security_Authentication_Provider_UsernamePasswordTest.php 1707 2009-01-07 10:37:30Z k-fish $
 */

/**
 * Testcase for web redirect authentication entry point
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Security_Authentication_Provider_UsernamePasswordTest.php 1707 2009-01-07 10:37:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class WebRedirectTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canForwardReturnsTrueForWebRequests() {
		$entryPoint = new WebRedirect();

		$this->assertTrue($entryPoint->canForward($this->getMock('F3\FLOW3\MVC\Web\Request')));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canForwardReturnsFalseForNonWebRequests() {
		$entryPoint = new WebRedirect();

		$this->assertFalse($entryPoint->canForward($this->getMock('F3\FLOW3\MVC\CLI\Request')));
		$this->assertFalse($entryPoint->canForward($this->getMock('F3\FLOW3\MVC\RequestInterface')));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException F3\FLOW3\Security\Exception\MissingConfiguration
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function startAuthenticationThrowsAnExceptionIfTheConfigurationOptionsAreMissing() {
		$entryPoint = new WebRedirect();
		$entryPoint->setOptions(array('something' => 'irrelevant'));

		$entryPoint->startAuthentication($this->getMock('F3\FLOW3\MVC\Web\Request'), $this->getMock('F3\FLOW3\MVC\Web\Response'));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException F3\FLOW3\Security\Exception\RequestTypeNotSupported
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function startAuthenticationThrowsAnExceptionIfItsCalledWithAnUnsupportedRequestType() {
		$entryPoint = new WebRedirect();

		$entryPoint->startAuthentication($this->getMock('F3\FLOW3\MVC\CLI\Request'), $this->getMock('F3\FLOW3\MVC\CLI\Response'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function startAuthenticationSetsTheCorrectValuesInTheResponseObject() {
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$response = $this->getMock('F3\FLOW3\MVC\Web\Response');

		$response->expects($this->once())->method('setStatus')->with(303);
		$response->expects($this->once())->method('setHeader')->with('Location', 'some/page');

		$entryPoint = new WebRedirect();
		$entryPoint->setOptions(array('uri' => 'some/page'));

		$entryPoint->startAuthentication($request, $response);
	}
}
?>