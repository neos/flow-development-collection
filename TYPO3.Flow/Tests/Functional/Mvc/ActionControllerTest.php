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

use TYPO3\FLOW3\Http\Client\Browser;
use TYPO3\FLOW3\Mvc\Routing\Route;
use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Response;
use TYPO3\FLOW3\Http\Uri;

/**
 * Functional tests for the ActionController
 */
class ActionControllerTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableHttpEnabled = TRUE;

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
		$route->setUriPattern('test/mvc/actioncontrollertesta(/{@action})');
		$route->setDefaults(array(
			'@package' => 'TYPO3.FLOW3',
			'@subpackage' => 'Tests\Functional\Mvc\Fixtures',
			'@controller' => 'ActionControllerTestA',
			'@action' => 'first',
			'@format' =>'html'
		));
		$route->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route);
	}

	/**
	 * Checks if a simple request is handled correctly. The route matching the
	 * specified URI defines a default action "first" which results in firstAction
	 * being called.
	 *
	 * @test
	 */
	public function defaultActionSpecifiedInrouteIsCalledAndResponseIsReturned() {
		$response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta');
		$this->assertEquals('First action was called', $response->getContent());
		$this->assertEquals('200 OK', $response->getStatus());
	}

	/**
	 * Checks if a simple request is handled correctly if another than the default
	 * action is specified.
	 *
	 * @test
	 */
	public function actionSpecifiedInActionRequestIsCalledAndResponseIsReturned() {
		$response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/second');
		$this->assertEquals('Second action was called', $response->getContent());
		$this->assertEquals('200 OK', $response->getStatus());
	}

	/**
	 * Checks if query parameters are handled correctly and default arguments are
	 * respected / overridden.
	 *
	 * @test
	 */
	public function queryStringOfAGetRequestIsParsedAndPassedToActionAsArguments() {
		$response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/third?secondArgument=bar&firstArgument=foo&third=baz');
		$this->assertEquals('thirdAction-foo-bar-baz-default', $response->getContent());
	}

	/**
	 * @test
	 */
	public function defaultTemplateIsResolvedAndUsedAccordingToConventions() {
		$response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/fourth?emailAddress=example@typo3.org');
		$this->assertEquals('Fourth action <b>example@typo3.org</b>', $response->getContent());
	}

	/**
	 * Bug #36913
	 *
	 * @test
	 */
	public function argumentsOfPutRequestArePassedToAction() {
		$request = Request::create(new Uri('http://localhost/test/mvc/actioncontrollertesta/put?getArgument=getValue'), 'PUT');
		$request->setContent("putArgument=first value");
		$request->setHeader('Content-Type', 'application/x-www-form-urlencoded');
		$request->setHeader('Content-Length', 54);

		$response = $this->browser->sendRequest($request);
		$this->assertEquals('putAction-first value-getValue', $response->getContent());
	}

	/**
	 * @test
	 */
	public function argumentsOfPutRequestWithJsonOrXmlTypeAreAlsoPassedToAction() {
		$request = Request::create(new Uri('http://localhost/test/mvc/actioncontrollertesta/put?getArgument=getValue'), 'PUT');
		$request->setHeader('Content-Type', 'application/json');
		$request->setHeader('Content-Length', 29);
		$request->setContent('{"putArgument":"first value"}');

		$response = $this->browser->sendRequest($request);
		$this->assertEquals('putAction-first value-getValue', $response->getContent());
	}
}
?>