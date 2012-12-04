<?php
namespace TYPO3\Flow\Tests\Functional\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Client\Browser;
use TYPO3\Flow\Mvc\Routing\Route;

/**
 * Functional tests for the ActionController
 */
class AbstractControllerTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * Additional setup: Routes
	 */
	public function setUp() {
		parent::setUp();

		$route = new Route();
		$route->setName('AbstractControllerTest Route 1');
		$route->setUriPattern('test/mvc/abstractcontrollertesta/{@action}');
		$route->setDefaults(array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\Fixtures',
			'@controller' => 'AbstractControllerTestA',
			'@format' =>'html'
		));
		$route->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route);
	}

	/**
	 * Checks if a request is forwarded to the second action.
	 *
	 * @test
	 */
	public function forwardPassesRequestToActionWithoutArguments() {
		$response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/forward?actionName=second');
		$this->assertEquals('Second action was called', $response->getContent());
	}

	/**
	 * Checks if a request is forwarded to the second action and passes the givn
	 * straight-value arguments.
	 *
	 * @test
	 */
	public function forwardPassesRequestToActionWithArguments() {
		$response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/forward?actionName=third&arguments[firstArgument]=foo&arguments[secondArgument]=bar');
		$this->assertEquals('thirdAction-foo-bar--default', $response->getContent());
	}

	/**
	 * Checks if a request is forwarded to the second action and passes the givn
	 * straight-value arguments.
	 *
	 * @test
	 */
	public function forwardPassesRequestToActionWithInternalArgumentsContainingObjects() {
		$response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/forward?actionName=fourth&passSomeObjectArguments=1&arguments[nonObject1]=First&arguments[nonObject2]=42');
		$this->assertEquals('fourthAction-First-42-TYPO3\Flow\Error\Message', $response->getContent());
	}

}
?>