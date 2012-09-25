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
 * Functional tests for the HTTP client internal request engine
 */
class InternalRequestEngineTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var boolean
	 */
	protected $testableHttpEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$route = new Route();
		$route->setName('Functional Test - Http::Client::InternalRequestEngine');
		$route->setUriPattern('test/security/restricted');
		$route->setDefaults(array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Security\Fixtures',
			'@controller' => 'Restricted',
			'@action' => 'admin',
			'@format' => 'html'
		));
		$this->router->addRoute($route);
	}

	/**
	 * Make sure that the security context tokens are initialized,
	 * making sure that the tokens match the request pattern of the request.
	 *
	 * Bug #37377
	 *
	 * @test
	 */
	public function securityContextContainsTokens() {
		$response = $this->browser->request('http://localhost/test/security/restricted');
		$this->assertEquals('#1222268609: ', $response->getHeader('X-Flow-ExceptionCode'));
	}

}
?>