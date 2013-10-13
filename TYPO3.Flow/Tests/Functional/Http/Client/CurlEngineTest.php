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

/**
 * Functional tests for the HTTP client internal request engine
 */
class CurlEngineTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!extension_loaded('curl')) {
			$this->markTestSkipped('Curl extension was not available.');
		}
		$curlEngine = $this->objectManager->get('TYPO3\Flow\Http\Client\CurlEngine');
		$this->browser->setRequestEngine($curlEngine);
	}

	/**
	 * Check if the Curl Engine can send a GET request to typo3.org
	 *
	 * @test
	 */
	public function getRequestReturnsResponse() {
		$response = $this->browser->request('http://typo3.org');
		$this->assertContains('This website is powered by TYPO3', $response->getContent());
	}

}
