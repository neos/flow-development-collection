<?php
namespace TYPO3\Flow\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
class DispatchComponent implements ComponentInterface {

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
	public function __construct(array $options = array()) {
		$this->options = $options;
	}

	/**
	 * @param MediaTypeConverterInterface $mediaTypeConverter
	 * @return void
	 */
	public function injectMediaTypeConverter(MediaTypeConverterInterface $mediaTypeConverter) {
		$this->propertyMappingConfiguration = new PropertyMappingConfiguration();
		$this->propertyMappingConfiguration->setTypeConverter($mediaTypeConverter);
	}

	/**
	 * Create an action request from stored route match values and dispatch to that
	 *
	 * @param ComponentContext $componentContext
	 * @return void
	 */
	public function handle(ComponentContext $componentContext) {
		$httpRequest = $componentContext->getHttpRequest();
		/** @var $actionRequest ActionRequest */
		$actionRequest = $this->objectManager->get('TYPO3\Flow\Mvc\ActionRequest', $httpRequest);
		$this->securityContext->setRequest($actionRequest);

		$routingMatchResults = $componentContext->getParameter('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults');

		$actionRequest->setArguments($this->mergeArguments($httpRequest, $routingMatchResults));
		$this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

		$componentContext->setParameter('TYPO3\Flow\Mvc\DispatchComponent', 'actionRequest' ,$actionRequest);
		$this->dispatcher->dispatch($actionRequest, $componentContext->getHttpResponse());
	}

	/**
	 * @param HttpRequest $httpRequest
	 * @param array $routingMatchResults
	 * @return array
	 */
	protected function mergeArguments(HttpRequest $httpRequest, array $routingMatchResults = NULL) {
		// HTTP body arguments
		$this->propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface', MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $httpRequest->getHeader('Content-Type'));
		$arguments = $this->propertyMapper->convert($httpRequest->getContent(), 'array', $this->propertyMappingConfiguration);

		// HTTP arguments (e.g. GET parameters)
		$arguments = Arrays::arrayMergeRecursiveOverrule($httpRequest->getArguments(), $arguments);

		// Routing results
		if ($routingMatchResults !== NULL) {
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
	protected function setDefaultControllerAndActionNameIfNoneSpecified(ActionRequest $actionRequest) {
		if ($actionRequest->getControllerName() === NULL) {
			$actionRequest->setControllerName('Standard');
		}
		if ($actionRequest->getControllerActionName() === NULL) {
			$actionRequest->setControllerActionName('index');
		}
	}

}