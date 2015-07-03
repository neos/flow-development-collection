<?php
namespace TYPO3\Flow\Mvc\Controller;

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

/**
 * An action controller for RESTful web services
 */
class RestController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * The current request
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * The response which will be returned by this action controller
	 * @var \TYPO3\Flow\Http\Response
	 */
	protected $response;

	/**
	 * Name of the action method argument which acts as the resource for the
	 * RESTful controller. If an argument with the specified name is passed
	 * to the controller, the show, update and delete actions can be triggered
	 * automatically.
	 *
	 * @var string
	 */
	protected $resourceArgumentName = 'resource';

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws \TYPO3\Flow\Mvc\Exception\NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		if ($this->request->getControllerActionName() === 'index') {
			$actionName = 'index';
			switch ($this->request->getHttpRequest()->getMethod()) {
				case 'HEAD':
				case 'GET' :
					$actionName = ($this->request->hasArgument($this->resourceArgumentName)) ? 'show' : 'list';
				break;
				case 'POST' :
					$actionName = 'create';
				break;
				case 'PUT' :
					if (!$this->request->hasArgument($this->resourceArgumentName)) {
						$this->throwStatus(400, NULL, 'No resource specified');
					}
					$actionName = 'update';
				break;
				case 'DELETE' :
					if (!$this->request->hasArgument($this->resourceArgumentName)) {
						$this->throwStatus(400, NULL, 'No resource specified');
					}
					$actionName = 'delete';
				break;
			}
			$this->request->setControllerActionName($actionName);
		}
		return parent::resolveActionMethodName();
	}

	/**
	 * Allow creation of resources in createAction()
	 *
	 * @return void
	 */
	protected function initializeCreateAction() {
		$propertyMappingConfiguration = $this->arguments[$this->resourceArgumentName]->getPropertyMappingConfiguration();
		$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		$propertyMappingConfiguration->allowAllProperties();
	}

	/**
	 * Allow modification of resources in updateAction()
	 *
	 * @return void
	 */
	protected function initializeUpdateAction() {
		$propertyMappingConfiguration = $this->arguments[$this->resourceArgumentName]->getPropertyMappingConfiguration();
		$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
		$propertyMappingConfiguration->allowAllProperties();
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will throw an exception
	 * if used with other request types.
	 *
	 * @param mixed $uri Either a string representation of a URI or a \TYPO3\Flow\Http\Uri object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 * @api
	 */
	protected function redirectToUri($uri, $delay = 0, $statusCode = 303) {
		// the parent method throws the exception, but we need to act afterwards
		// thus the code in catch - it's the expected state
		try {
			parent::redirectToUri($uri, $delay, $statusCode);
		} catch (\TYPO3\Flow\Mvc\Exception\StopActionException $exception) {
			if ($this->request->getFormat() === 'json') {
				$this->response->setContent('');
			}
			throw $exception;
		}
	}
}
