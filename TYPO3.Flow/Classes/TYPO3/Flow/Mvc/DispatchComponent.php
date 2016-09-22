<?php
namespace TYPO3\Flow\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Component\ComponentInterface;
use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Utility\Arrays;

/**
 * A dispatch component
 */
class DispatchComponent implements ComponentInterface
{
    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * PropertyMapping configuration set in injectMediaTypeConverter()
     *
     * @var PropertyMappingConfiguration
     */
    protected $propertyMappingConfiguration;

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
     * @param MediaTypeConverterInterface $mediaTypeConverter
     * @return void
     */
    public function injectMediaTypeConverter(MediaTypeConverterInterface $mediaTypeConverter)
    {
        $this->propertyMappingConfiguration = new PropertyMappingConfiguration();
        $this->propertyMappingConfiguration->setTypeConverter($mediaTypeConverter);
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

        $componentContext->setParameter(DispatchComponent::class, 'actionRequest', $actionRequest);
        $this->dispatcher->dispatch($actionRequest, $componentContext->getHttpResponse());
    }

    /**
     * @param HttpRequest $httpRequest
     * @param array $routingMatchResults
     * @return array
     */
    protected function mergeArguments(HttpRequest $httpRequest, array $routingMatchResults = null)
    {
        // HTTP body arguments
        $this->propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $httpRequest->getHeader('Content-Type'));
        $arguments = $this->propertyMapper->convert($httpRequest->getContent(), 'array', $this->propertyMappingConfiguration);

        // HTTP arguments (e.g. GET parameters)
        $arguments = Arrays::arrayMergeRecursiveOverrule($httpRequest->getArguments(), $arguments);

        // Routing results
        if ($routingMatchResults !== null) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $routingMatchResults);
        }
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
