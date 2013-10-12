<?php
namespace TYPO3\Flow\Tests\Functional\Http\Client;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\Routing\Route;

/**
 * Functional tests for the HTTP browser
 */
class BrowserTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->registerRoute(
			'Functional Test - Http::Client::BrowserTest',
			'test/http/redirecting(/{@action})',
			array(
				'@package' => 'TYPO3.Flow',
				'@subpackage' => 'Tests\Functional\Http\Fixtures',
				'@controller' => 'Redirecting',
				'@action' => 'fromHere',
				'@format' => 'html'
			)
		);
	}

	/**
	 * Check if the browser can handle redirects
	 *
	 * @test
	 */
	public function redirectsAreFollowed() {
		$response = $this->browser->request('http://localhost/test/http/redirecting');
		$this->assertEquals('arrived.', $response->getContent());
	}

	/**
	 * Check if the browser doesn't follow redirects if told so
	 *
	 * @test
	 */
	public function redirectsAreNotFollowedIfSwitchedOff() {
		$this->browser->setFollowRedirects(FALSE);
		$response = $this->browser->request('http://localhost/test/http/redirecting');
		$this->assertNotContains('arrived.', $response->getContent());
		$this->assertEquals(303, $response->getStatusCode());
		$this->assertEquals('http://localhost/test/http/redirecting/tohere', $response->getHeader('Location'));
	}

}
