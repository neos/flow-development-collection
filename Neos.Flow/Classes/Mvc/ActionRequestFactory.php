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
     * @param array<mixed> $additionalArguments
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

        return $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);
    }

    /**
     * @return array<mixed>
     */
    protected function mergeHttpRequestArguments(ServerRequestInterface $httpRequest): array
    {
        $arguments = $httpRequest->getQueryParams();
        $parsedBody = $httpRequest->getParsedBody();
        if (is_array($parsedBody) && $parsedBody !== []) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $parsedBody);
        }

        $uploadedFiles = UploadedFilesHelper::upcastUploadedFiles($httpRequest->getUploadedFiles(), $arguments);

        return Arrays::arrayMergeRecursiveOverrule($arguments, $uploadedFiles);
    }

    /**
     * @param array<mixed> $arguments
     * @param array<mixed> $additionalArguments
     * @return array<mixed>
     */
    protected function mergeHttpRequestArgumentsWithAdditionalArguments(array $arguments, array $additionalArguments): array
    {
        if (!empty($additionalArguments)) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $additionalArguments);
        }

        return $arguments;
    }

    protected function prepareActionRequest(ServerRequestInterface $httpRequest): ActionRequest
    {
        return ActionRequest::fromHttpRequest($httpRequest);
    }

    /**
     * Set the default controller and action names if none has been specified.
     *
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
