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

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the ActionRequest
 */
class ActionRequestTest extends FunctionalTestCase {

	/**
	 * @test
	 */
	public function actionRequestStripsParentHttpRequest() {
		$httpRequest = Request::create(new Uri('http://typo3.org'));

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setControllerActionName('foo');
		$serializedActionRequest = serialize($actionRequest);

		/* @var $unserializedActionRequest ActionRequest */
		$unserializedActionRequest = unserialize($serializedActionRequest);
		$this->assertNull($unserializedActionRequest->getParentRequest(), 'Parent HTTP request should be NULL after deserialization');
		$this->assertSame('foo', $unserializedActionRequest->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function actionRequestDoesNotStripParentActionRequest() {
		$httpRequest = Request::create(new Uri('http://typo3.org'));

		$parentActionRequest = new ActionRequest($httpRequest);
		$actionRequest = new ActionRequest($parentActionRequest);
		$serializedActionRequest = serialize($actionRequest);

		/* @var $unserializedActionRequest ActionRequest */
		$unserializedActionRequest = unserialize($serializedActionRequest);
		$this->assertNotNull($unserializedActionRequest->getParentRequest(), 'Parent action request should not be NULL after deserialization');
	}
}
