<?php
namespace Neos\Flow\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Http\Request as HttpRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Neos\Flow\Security\Context;
use Neos\Utility\Arrays;

/**
 * A dispatch component
 */
class DispatchComponent implements ComponentInterface
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @Flow\Inject(lazy=false)
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject(lazy=false)
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * Options of this component
     *
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Create an action request from stored route match values and dispatch to that
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $componentContext = $this->prepareActionRequest($componentContext);
        $actionRequest = $componentContext->getParameter(DispatchComponent::class, 'actionRequest');
        $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);
        $actionResponse = $this->prepareActionResponse($componentContext->getHttpResponse());
        $this->dispatcher->dispatch($actionRequest, $actionResponse);
        // TODO: This should change in next major when the action response is no longer a HTTP response for backward compatibility.
        $componentContext->replaceHttpResponse($actionResponse);
    }

    /**
     * Create ActionRequest with arguments from body and routing merged and add it to the component context.
     *
     * TODO: This could be a separate HTTP component (ActionRequestFactoryComponent) that sits in the chain before the DispatchComponent.
     *
     * @param ComponentContext $componentContext
     * @return ComponentContext
     */
    protected function prepareActionRequest(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();
        $arguments = $httpRequest->getArguments();

        $parsedBody = $this->parseRequestBody($httpRequest);
        if ($parsedBody !== null) {
            $httpRequest = $httpRequest->withParsedBody($parsedBody);
        }
        if (is_array($parsedBody) && $parsedBody !== []) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $parsedBody);
        }

        $routingMatchResults = $componentContext->getParameter(Routing\RoutingComponent::class, 'matchResults');
        if ($routingMatchResults !== null) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $routingMatchResults);
        }

        /** @var $actionRequest ActionRequest */
        $actionRequest = $this->objectManager->get(ActionRequest::class, $httpRequest);

        $actionRequest->setArguments($arguments);
        $this->securityContext->setRequest($actionRequest);
        $componentContext->replaceHttpRequest($httpRequest);

        $componentContext->setParameter(DispatchComponent::class, 'actionRequest', $actionRequest);

        return $componentContext;
    }

    /**
     * Parses the request body according to the media type.
     *
     * @param HttpRequest $httpRequest
     * @return null|array|string|integer
     */
    protected function parseRequestBody(HttpRequest $httpRequest)
    {
        $requestBody = $httpRequest->getContent();
        if ($requestBody === null || $requestBody === '') {
            return $requestBody;
        }

        $mediaTypeConverter = $this->objectManager->get(MediaTypeConverterInterface::class);
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($mediaTypeConverter);
        $propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $httpRequest->getHeader('Content-Type'));
        // FIXME: The MediaTypeConverter returns an empty array for "error cases", which might be unintended
        $arguments = $this->propertyMapper->convert($requestBody, 'array', $propertyMappingConfiguration);

        return $arguments;
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

    /**
     * Prepares the ActionResponse to be dispatched
     *
     * TODO: Needs to be adapted for next major when we only deliver an action response inside the dispatch.
     *
     * @param \Psr\Http\Message\ResponseInterface $httpResponse
     * @return ActionResponse|\Psr\Http\Message\ResponseInterface
     */
    protected function prepareActionResponse(\Psr\Http\Message\ResponseInterface $httpResponse): ActionResponse
    {
        $rawResponse = implode("\r\n", ResponseInformationHelper::prepareHeaders($httpResponse));
        $rawResponse .= "\r\n\r\n";
        $rawResponse .= $httpResponse->getBody()->getContents();

        return ActionResponse::createFromRaw($rawResponse);
    }
}
