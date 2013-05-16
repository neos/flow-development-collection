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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Context;

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
	 * Create an action request from stored route match values and dispatch to that
	 *
	 * @param ComponentContext $componentContext
	 * @return void
	 */
	public function handle(ComponentContext $componentContext) {
		/** @var $actionRequest ActionRequest */
		$actionRequest = $this->objectManager->get('TYPO3\Flow\Mvc\ActionRequest', $componentContext->getHttpRequest());
		$this->securityContext->setRequest($actionRequest);

		$matchResults = $componentContext->getParameter('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults');
		if ($matchResults !== NULL) {
			$actionRequest->setArguments($matchResults);
		}
		$this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

		$componentContext->setParameter('TYPO3\Flow\Mvc\DispatchComponent', 'actionRequest' ,$actionRequest);
		$this->dispatcher->dispatch($actionRequest, $componentContext->getHttpResponse());
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