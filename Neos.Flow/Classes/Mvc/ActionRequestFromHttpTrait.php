<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Http\Helper\ArgumentsHelper;
use Neos\Flow\Http\Helper\UploadedFilesHelper;
use Neos\Utility\Arrays;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
trait ActionRequestFromHttpTrait
{
    /**
     * @param ServerRequestInterface $httpRequest
     * @param array $additionalArguments
     * @return ActionRequest
     * @throws Exception\InvalidArgumentNameException
     * @throws Exception\InvalidArgumentTypeException
     */
    protected function createActionRequest(ServerRequestInterface $httpRequest, array $additionalArguments = [])
    {
        // TODO: Move arguments merging into action request as lazy operation to avoid duplicating the arguments?
        $arguments = ArgumentsHelper::buildUnifiedArguments($httpRequest->getQueryParams(), $httpRequest->getParsedBody(), UploadedFilesHelper::untangleFilesArray($_FILES));

        /** @var $actionRequest ActionRequest */
        $actionRequest = new ActionRequest($httpRequest);
        if (!empty($additionalArguments)) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $additionalArguments);
        }

        $actionRequest->setArguments($arguments);
        $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

        return $actionRequest;
    }

    /**
     * Set the default controller and action names if none has been specified.
     *
     * @param ActionRequest $actionRequest
     * @return void
     */
    protected function setDefaultControllerAndActionNameIfNoneSpecified(ActionRequest $actionRequest)
    {
        if ($actionRequest->getControllerName() === null) {
            $actionRequest->setControllerName('Standard');
        }
        if ($actionRequest->getControllerActionName() === null) {
            $actionRequest->setControllerActionName('index');
        }
    }
}
