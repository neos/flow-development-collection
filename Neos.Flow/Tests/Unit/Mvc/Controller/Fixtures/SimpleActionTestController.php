<?php
namespace Neos\Flow\Tests\Unit\Mvc\Controller\Fixtures;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\SimpleActionController;

/**
 *
 */
class SimpleActionTestController extends SimpleActionController
{
    public function addTestContentAction(ActionRequest $actionRequest): ActionResponse
    {
        $response = new ActionResponse();
        $response->setContent('Simple');
        return $response;
    }
}
