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
        $httpRequest = $componentContext->getHttpRequest();
        /** @var $actionRequest ActionRequest */
        $actionRequest = $this->objectManager->get(ActionRequest::class, $httpRequest);
        $this->securityContext->setRequest($actionRequest);

        $routingMatchResults = $componentContext->getParameter(Routing\RoutingComponent::class, 'matchResults');

        $actionRequest->setArguments($this->mergeArguments($httpRequest, $routingMatchResults));
        $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

        $componentContext->setParameter(self::class, 'actionRequest', $actionRequest);
        $this->dispatcher->dispatch($actionRequest, $componentContext->getHttpResponse());
    }

    /**
     * @param HttpRequest $httpRequest
     * @param array $routingMatchResults
     * @return array
     */
    protected function mergeArguments(HttpRequest $httpRequest, array $routingMatchResults = null)
    {
        $arguments = $this->parseRequestBody($httpRequest);

        // HTTP arguments (e.g. GET parameters)
        $arguments = Arrays::arrayMergeRecursiveOverrule($httpRequest->getArguments(), $arguments);

        // Routing results
        if ($routingMatchResults !== null) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $routingMatchResults);
        }
        return $arguments;
    }

    /**
     * Parses the request body according to the media type.
     *
     * @param HttpRequest $httpRequest
     * @return array
     */
    protected function parseRequestBody(HttpRequest $httpRequest)
    {
        $requestBody = $httpRequest->getContent();
        if ($requestBody === null || $requestBody === '') {
            return [];
        }

        $mediaTypeConverter = $this->objectManager->get(MediaTypeConverterInterface::class);
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($mediaTypeConverter);
        $propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $httpRequest->getHeader('Content-Type'));
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
}
