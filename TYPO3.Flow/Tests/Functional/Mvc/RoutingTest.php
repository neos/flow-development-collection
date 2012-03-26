<?php
namespace TYPO3\FLOW3\Tests\Functional\Mvc;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Http\Client;
use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Response;
use TYPO3\FLOW3\Http\Uri;

/**
 * Functional tests for the Router
 *
 * HINT: The routes used in these tests are defined in the Routes.yaml file in the
 *       Testing context of the FLOW3 package configuration.
 */
class RoutingTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\Router
	 */
	protected $router;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->router = $this->objectManager->get('TYPO3\FLOW3\Mvc\Routing\Router');
		$routesConfiguration = $this->objectManager->get('TYPO3\FLOW3\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
	}

	/**
	 * @test
	 */
	public function placeholder() {
		$this->assertTrue(TRUE);
	}

}
?>