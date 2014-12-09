<?php
namespace TYPO3\Flow\Tests\Unit\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;

/**
 * Test case for the Http Cookie class
 */
class BrowserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Http\Client\Browser
	 */
	protected $browser;

	/**
	 *
	 */
	protected function setUp() {
		parent::setUp();
		$this->browser = new \TYPO3\Flow\Http\Client\Browser();
	}

	/**
	 * @test
	 */
	public function requestingUriQueriesRequestEngine() {
		$requestEngine = $this->getMock('TYPO3\Flow\Http\Client\RequestEngineInterface');
		$requestEngine
			->expects($this->once())
			->method('sendRequest')
			->with($this->isInstanceOf('TYPO3\Flow\Http\Request'))
			->will($this->returnValue(new Response()));
		$this->browser->setRequestEngine($requestEngine);
		$this->browser->request('http://localhost/foo');
	}

	/**
	 * @test
	 */
	public function automaticHeadersAreSetOnEachRequest() {
		$requestEngine = $this->getMock('TYPO3\Flow\Http\Client\RequestEngineInterface');
		$requestEngine
			->expects($this->any())
			->method('sendRequest')
			->will($this->returnValue(new Response()));
		$this->browser->setRequestEngine($requestEngine);

		$this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
		$this->browser->request('http://localhost/foo');

		$this->assertTrue($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
		$this->assertSame('Acme', $this->browser->getLastRequest()->getHeader('X-Test-Header'));
	}

	/**
	 * @test
	 * @depends automaticHeadersAreSetOnEachRequest
	 */
	public function automaticHeadersCanBeRemovedAgain() {
		$requestEngine = $this->getMock('TYPO3\Flow\Http\Client\RequestEngineInterface');
		$requestEngine
			->expects($this->once())
			->method('sendRequest')
			->will($this->returnValue(new Response()));
		$this->browser->setRequestEngine($requestEngine);

		$this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
		$this->browser->removeAutomaticRequestHeader('X-Test-Header');
		$this->browser->request('http://localhost/foo');
		$this->assertFalse($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
	}

	/**
	 * @test
	 */
	public function browserFollowsRedirectionIfResponseTellsSo() {
		$initialUri = new Uri('http://localhost/foo');
		$redirectUri = new Uri('http://localhost/goToAnotherFoo');

		$firstResponse = new Response();
		$firstResponse->setStatus(301);
		$firstResponse->setHeader('Location', (string)$redirectUri);
		$secondResponse = new Response();
		$secondResponse->setStatus(202);

		$requestEngine = $this->getMock('TYPO3\Flow\Http\Client\RequestEngineInterface');
		$requestEngine
			->expects($this->at(0))
			->method('sendRequest')
			->with($this->attributeEqualTo('uri', $initialUri))
			->will($this->returnValue($firstResponse));
		$requestEngine
			->expects($this->at(1))
			->method('sendRequest')
			->with($this->attributeEqualTo('uri', $redirectUri))
			->will($this->returnValue($secondResponse));

		$this->browser->setRequestEngine($requestEngine);
		$actual = $this->browser->request($initialUri);
		$this->assertSame($secondResponse, $actual);
	}

	/**
	 * @test
	 */
	public function browserDoesNotRedirectOnLocationHeaderButNot3xxResponseCode() {
		$twoZeroOneResponse = new Response();
		$twoZeroOneResponse->setStatus(201);
		$twoZeroOneResponse->setHeader('Location', 'http://localhost/createdResource/isHere');

		$requestEngine = $this->getMock('TYPO3\Flow\Http\Client\RequestEngineInterface');
		$requestEngine
			->expects($this->once())
			->method('sendRequest')
			->will($this->returnValue($twoZeroOneResponse));

		$this->browser->setRequestEngine($requestEngine);
		$actual = $this->browser->request('http://localhost/createSomeResource');
		$this->assertSame($twoZeroOneResponse, $actual);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Http\Client\InfiniteRedirectionException
	 */
	public function browserHaltsOnAttemptedInfiniteRedirectionLoop() {
		$wildResponses = array();
		$wildResponses[0] = new Response();
		$wildResponses[0]->setStatus(301);
		$wildResponses[0]->setHeader('Location', 'http://localhost/pleaseGoThere');
		$wildResponses[1] = new Response();
		$wildResponses[1]->setStatus(301);
		$wildResponses[1]->setHeader('Location', 'http://localhost/ahNoPleaseRatherGoThere');
		$wildResponses[2] = new Response();
		$wildResponses[2]->setStatus(301);
		$wildResponses[2]->setHeader('Location', 'http://localhost/youNoWhatISendYouHere');
		$wildResponses[3] = new Response();
		$wildResponses[3]->setStatus(301);
		$wildResponses[3]->setHeader('Location', 'http://localhost/ahNoPleaseRatherGoThere');

		$requestEngine = $this->getMock('TYPO3\Flow\Http\Client\RequestEngineInterface');
		for ($i=0; $i<=3; $i++) {
			$requestEngine
				->expects($this->at($i))
				->method('sendRequest')
				->will($this->returnValue($wildResponses[$i]));
		}

		$this->browser->setRequestEngine($requestEngine);
		$this->browser->request('http://localhost/mayThePaperChaseBegin');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Http\Client\InfiniteRedirectionException
	 */
	public function browserHaltsOnExceedingMaximumRedirections() {
		$requestEngine = $this->getMock('TYPO3\Flow\Http\Client\RequestEngineInterface');
		for ($i=0; $i<=10; $i++) {
			$response = new Response();
			$response->setHeader('Location', 'http://localhost/this/willLead/you/knowhere/' . $i);
			$response->setStatus(301);
			$requestEngine
				->expects($this->at($i))
				->method('sendRequest')
				->will($this->returnValue($response));
		}

		$this->browser->setRequestEngine($requestEngine);
		$this->browser->request('http://localhost/some/initialRequest');
	}

}
