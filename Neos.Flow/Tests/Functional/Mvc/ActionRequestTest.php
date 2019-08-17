<?php
namespace Neos\Flow\Tests\Functional\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Tests\FunctionalTestCase;

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
        $httpRequest = new ServerRequest('GET', new Uri('http://neos.io'));

        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setControllerActionName('foo');
        $serializedActionRequest = serialize($actionRequest);

        /* @var $unserializedActionRequest ActionRequest */
        $unserializedActionRequest = unserialize($serializedActionRequest);
        self::assertNull($unserializedActionRequest->getParentRequest(), 'Parent HTTP request should be NULL after deserialization');
        self::assertSame('foo', $unserializedActionRequest->getControllerActionName());
    }

    /**
     * @test
     */
    public function actionRequestDoesNotStripParentActionRequest()
    {
        $httpRequest = new ServerRequest('GET', new Uri('http://neos.io'));

        $parentActionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest = $parentActionRequest->createSubRequest();
        $serializedActionRequest = serialize($actionRequest);

        /* @var $unserializedActionRequest ActionRequest */
        $unserializedActionRequest = unserialize($serializedActionRequest);
        self::assertNotNull($unserializedActionRequest->getParentRequest(), 'Parent action request should not be NULL after deserialization');
    }
}
