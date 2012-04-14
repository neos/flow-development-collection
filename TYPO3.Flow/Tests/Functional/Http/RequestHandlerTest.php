<?php
namespace TYPO3\FLOW3\Tests\Functional\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Mvc\Routing\Route;

/**
 * Functional tests for the HTTP Request Handler
 */
class RequestHandlerTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableHttpEnabled = TRUE;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @test
	 */
	public function httpRequestIsConvertedToAnActionRequestAndDispatchedToTheRespectiveController() {
		$foundRoute = FALSE;
		foreach ($this->router->getRoutes() as $route) {
			if ($route->getName() === 'FLOW3 :: FLOW3 :: Functional Test: HTTP - FooController') {
				$foundRoute = TRUE;
			}
		}

		if (!$foundRoute) {
			$this->markTestSkipped('In this distribution the FLOW3 routes are not included into the global configuration.');
			return;
		}

		$_SERVER = array (
			'HTTP_HOST' => 'localhost',
			'REQUEST_METHOD' => 'GET',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/typo3/flow3/test/http/foo',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$requestHandler = $this->getAccessibleMock('TYPO3\FLOW3\Http\RequestHandler', array('boot'), array(self::$bootstrap));
		$requestHandler->exit = function() {};
		$requestHandler->handleRequest();

		$this->expectOutputString('FooController responded');
	}

}
?>