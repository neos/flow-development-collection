<?php
namespace Neos\Flow\Mvc\Controller;

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
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;

/**
 * An action controller for RESTful web services
 */
class RestController extends ActionController
{
    /**
     * The current request
     * @var ActionRequest
     */
    protected $request;

    /**
     * The response which will be returned by this action controller
     * @var Response
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
     * @throws NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
     */
    protected function resolveActionMethodName()
    {
        if ($this->request->getControllerActionName() === 'index') {
            $actionName = 'index';
            switch ($this->request->getHttpRequest()->getMethod()) {
                case 'HEAD':
                case 'GET':
                    $actionName = ($this->request->hasArgument($this->resourceArgumentName)) ? 'show' : 'list';
                break;
                case 'POST':
                    $actionName = 'create';
                break;
                case 'PUT':
                    if (!$this->request->hasArgument($this->resourceArgumentName)) {
                        $this->throwStatus(400, null, 'No resource specified');
                    }
                    $actionName = 'update';
                break;
                case 'DELETE':
                    if (!$this->request->hasArgument($this->resourceArgumentName)) {
                        $this->throwStatus(400, null, 'No resource specified');
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
    protected function initializeCreateAction()
    {
        $propertyMappingConfiguration = $this->arguments[$this->resourceArgumentName]->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
        $propertyMappingConfiguration->allowAllProperties();
    }

    /**
     * Allow modification of resources in updateAction()
     *
     * @return void
     */
    protected function initializeUpdateAction()
    {
        $propertyMappingConfiguration = $this->arguments[$this->resourceArgumentName]->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
        $propertyMappingConfiguration->allowAllProperties();
    }

    /**
     * Redirects the web request to another uri.
     *
     * NOTE: This method only supports web requests and will throw an exception
     * if used with other request types.
     *
     * @param mixed $uri Either a string representation of a URI or a \Neos\Flow\Http\Uri object
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @return void
     * @throws StopActionException
     * @api
     */
    protected function redirectToUri($uri, $delay = 0, $statusCode = 303)
    {
        // the parent method throws the exception, but we need to act afterwards
        // thus the code in catch - it's the expected state
        try {
            parent::redirectToUri($uri, $delay, $statusCode);
        } catch (StopActionException $exception) {
            if ($this->request->getFormat() === 'json') {
                $this->response->setContent('');
            }
            throw $exception;
        }
    }
}
