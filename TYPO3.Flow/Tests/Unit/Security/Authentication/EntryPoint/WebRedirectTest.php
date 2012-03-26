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

use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Response;
use TYPO3\FLOW3\Http\Uri;
use TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect;

/**
 * Testcase for web redirect authentication entry point
 */
class WebRedirectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\MissingConfigurationException
	 */
	public function startAuthenticationThrowsAnExceptionIfTheConfigurationOptionsAreMissing() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$response = new Response();

		$entryPoint = new WebRedirect();
		$entryPoint->setOptions(array('something' => 'irrelevant'));

		$entryPoint->startAuthentication($request->getHttpRequest(), $response);
	}

	/**
	 * @test
	 */
	public function startAuthenticationSetsTheCorrectValuesInTheResponseObject() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$response = new Response();

		$entryPoint = new WebRedirect();
		$entryPoint->setOptions(array('uri' => 'some/page'));

		$entryPoint->startAuthentication($request->getHttpRequest(), $response);

		$this->assertEquals('303', substr($response->getStatus(), 0, 3));
		$this->assertEquals('http://robertlemke.com/some/page', $response->getHeader('Location'));
		$this->assertEquals(array('uri' => 'some/page'), $entryPoint->getOptions());
	}
}
?>
