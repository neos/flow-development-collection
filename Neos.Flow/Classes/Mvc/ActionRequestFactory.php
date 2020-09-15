<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Http\Helper\UploadedFilesHelper;
use Neos\Utility\Arrays;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Creates an ActionRequest from a PSR-7 http request and sets appropriate defaults.
 */
class ActionRequestFactory
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
    public function createActionRequest(ServerRequestInterface $httpRequest, array $additionalArguments = []): ActionRequest
    {
        $arguments = $this->mergeHttpRequestArguments($httpRequest);
        $arguments = $this->mergeHttpRequestArgumentsWithAdditionalArguments($arguments, $additionalArguments);
        $actionRequest = $this->prepareActionRequest($httpRequest);

        $actionRequest->setArguments($arguments);
        $actionRequest = $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

        return $actionRequest;
    }

    /**
     * @param ServerRequestInterface $httpRequest
     * @return array
     */
    protected function mergeHttpRequestArguments(ServerRequestInterface $httpRequest): array
    {
        $arguments = $httpRequest->getQueryParams();
        $parsedBody = $httpRequest->getParsedBody();
        if (is_array($parsedBody) && $parsedBody !== []) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $parsedBody);
        }

        $uploadedFiles = UploadedFilesHelper::upcastUploadedFiles($httpRequest->getUploadedFiles(), $arguments);
        $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $uploadedFiles);

        return $arguments;
    }

    /**
     * @param array $arguments
     * @param array $additionalArguments
     * @return array
     */
    protected function mergeHttpRequestArgumentsWithAdditionalArguments(array $arguments, array $additionalArguments): array
    {
        if (!empty($additionalArguments)) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $additionalArguments);
        }

        return $arguments;
    }

    /**
     * @param ServerRequestInterface $httpRequest
     * @return ActionRequest
     */
    protected function prepareActionRequest(ServerRequestInterface $httpRequest): ActionRequest
    {
        return ActionRequest::fromHttpRequest($httpRequest);
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
        if ($actionRequest->getControllerName() === '') {
            $actionRequest->setControllerName('Standard');
        }
        if ($actionRequest->getControllerActionName() === '') {
            $actionRequest->setControllerActionName('index');
        }

        return $actionRequest;
    }
}
