<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authentication\EntryPoint;

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
 * Testcase for HTTP Basic Auth authentication entry point
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class HttpBasicTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canForwardReturnsTrueForWebRequests() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();

		$this->assertTrue($entryPoint->canForward($this->getMock('TYPO3\FLOW3\MVC\Web\Request')));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canForwardReturnsFalseForNonWebRequests() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();

		$this->assertFalse($entryPoint->canForward($this->getMock('TYPO3\FLOW3\MVC\CLI\Request')));
		$this->assertFalse($entryPoint->canForward($this->getMock('TYPO3\FLOW3\MVC\RequestInterface')));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function startAuthenticationThrowsAnExceptionIfItsCalledWithAnUnsupportedRequestType() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();

		$entryPoint->startAuthentication($this->getMock('TYPO3\FLOW3\MVC\CLI\Request'), $this->getMock('TYPO3\FLOW3\MVC\CLI\Response'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function startAuthenticationSetsTheCorrectValuesInTheResponseObject() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$response = $this->getMock('TYPO3\FLOW3\MVC\Web\Response', array('setStatus', 'setContent', 'setHeader'));

		$response->expects($this->once())->method('setStatus')->with(401);
		$response->expects($this->once())->method('setHeader')->with('WWW-Authenticate', 'Basic realm="realm string"');
		$response->expects($this->once())->method('setContent')->with('Authorization required!');

		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();
		$entryPoint->setOptions(array('realm' => 'realm string'));

		$entryPoint->startAuthentication($request, $response);
	}
}
?>