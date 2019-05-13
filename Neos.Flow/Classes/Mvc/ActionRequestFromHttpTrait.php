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
     * @throws Exception\InvalidActionNameException
     * @throws Exception\InvalidArgumentNameException
     * @throws Exception\InvalidArgumentTypeException
     * @throws Exception\InvalidControllerNameException
     */
    protected function createActionRequest(ServerRequestInterface $httpRequest, array $additionalArguments = []): ActionRequest
    {
        $arguments = $httpRequest->getQueryParams();
        if (is_array($httpRequest->getParsedBody())) {
            $arguments = ArgumentsHelper::mergeArgumentArrays($arguments, $httpRequest->getParsedBody());
        }

        $uploadedFiles = UploadedFilesHelper::upcastUploadedFiles($httpRequest->getUploadedFiles(), $arguments);
        $arguments = ArgumentsHelper::mergeArgumentArrays($arguments, $uploadedFiles);

        /** @var $actionRequest ActionRequest */
        $actionRequest = $this->objectManager->get(ActionRequest::class);
        //new ActionRequest($httpRequest);
        if (!empty($additionalArguments)) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $additionalArguments);
        }

        $actionRequest->setArguments($arguments);
        $actionRequest = $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

        return $actionRequest;
    }

    /**
     * Set the default controller and action names if none has been specified.
     *
     * @param ActionRequest $actionRequest
     * @return ActionRequest
     * @throws Exception\InvalidActionNameException
     * @throws Exception\InvalidControllerNameException
     */
    protected function setDefaultControllerAndActionNameIfNoneSpecified(ActionRequest $actionRequest): ActionRequest
    {
        if ($actionRequest->getControllerName() === null) {
            $actionRequest->setControllerName('Standard');
        }
        if ($actionRequest->getControllerActionName() === null) {
            $actionRequest->setControllerActionName('index');
        }

        return $actionRequest;
    }
}
