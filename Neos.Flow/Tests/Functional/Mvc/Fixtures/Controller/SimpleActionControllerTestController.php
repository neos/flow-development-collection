<?php
namespace Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\SimpleActionController;

/**
 *
 */
class SimpleActionControllerTestController extends SimpleActionController
{
    public function indexAction(ActionRequest $actionRequest): ActionResponse
    {
        $response = new ActionResponse();
        $response->setContent('index');

        return $response;
    }

    public function simpleReponseAction(ActionRequest $actionRequest): ActionResponse
    {
        $response = new ActionResponse();
        $response->setContent('Simple');

        return $response;
    }

    public function jsonResponseAction(ActionRequest $actionRequest): ActionResponse
    {
        $response = new ActionResponse();
        $response->setContent(json_encode(['foo' => 'bar', 'baz' => 123]));
        $response->setContentType('application/json');

        return $response;
    }

    public function argumentsAction(ActionRequest $actionRequest): ActionResponse
    {
        $response = new ActionResponse();
        $response->setContent(strtolower($actionRequest->getArgument('testArgument')));
        if ($actionRequest->getFormat() === 'html') {
            $response->setContentType('text/html');
        }

        return $response;
    }
}
