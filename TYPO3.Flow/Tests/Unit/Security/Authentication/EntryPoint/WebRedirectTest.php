<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authentication\EntryPoint;

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
 * Testcase for web redirect authentication entry point
 *
 */
class WebRedirectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 */
	public function canForwardReturnsTrueForWebRequests() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect();

		$this->assertTrue($entryPoint->canForward($this->getMock('TYPO3\FLOW3\Mvc\ActionRequest')));
	}

	/**
	 * @test
	 * @category unit
	 */
	public function canForwardReturnsFalseForNonWebRequests() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect();

		$this->assertFalse($entryPoint->canForward($this->getMock('TYPO3\FLOW3\Cli\Request')));
		$this->assertFalse($entryPoint->canForward($this->getMock('TYPO3\FLOW3\Mvc\RequestInterface')));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException TYPO3\FLOW3\Security\Exception\MissingConfigurationException
	 */
	public function startAuthenticationThrowsAnExceptionIfTheConfigurationOptionsAreMissing() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect();
		$entryPoint->setOptions(array('something' => 'irrelevant'));

		$entryPoint->startAuthentication($this->getMock('TYPO3\FLOW3\Mvc\ActionRequest'), $this->getMock('TYPO3\FLOW3\Mvc\Web\Response'));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 */
	public function startAuthenticationThrowsAnExceptionIfItsCalledWithAnUnsupportedRequestType() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect();

		$entryPoint->startAuthentication($this->getMock('TYPO3\FLOW3\Cli\Request'), $this->getMock('TYPO3\FLOW3\Cli\Response'));
	}

	/**
	 * @test
	 * @category unit
	 */
	public function startAuthenticationSetsTheCorrectValuesInTheResponseObject() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$response = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');

		$response->expects($this->once())->method('setStatus')->with(303);
		$response->expects($this->once())->method('setHeader')->with('Location', 'some/page');

		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect();
		$entryPoint->setOptions(array('uri' => 'some/page'));

		$entryPoint->startAuthentication($request, $response);
	}
}
?>