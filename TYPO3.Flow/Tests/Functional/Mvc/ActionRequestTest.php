<?php
namespace TYPO3\Flow\Tests\Functional\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the ActionRequest
 */
class ActionRequestTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function actionRequestStripsParentHttpRequest()
    {
        $httpRequest = Request::create(new Uri('http://neos.io'));

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
    public function actionRequestDoesNotStripParentActionRequest()
    {
        $httpRequest = Request::create(new Uri('http://neos.io'));

        $parentActionRequest = new ActionRequest($httpRequest);
        $actionRequest = new ActionRequest($parentActionRequest);
        $serializedActionRequest = serialize($actionRequest);

        /* @var $unserializedActionRequest ActionRequest */
        $unserializedActionRequest = unserialize($serializedActionRequest);
        $this->assertNotNull($unserializedActionRequest->getParentRequest(), 'Parent action request should not be NULL after deserialization');
    }
}
