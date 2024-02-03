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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Exception\InvalidActionVisibilityException;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Psr\Http\Message\UriInterface;

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
     * @var ActionResponse
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
     * @param ActionRequest $request
     * @return string The action method name
     * @throws NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
     * @throws StopActionException
     * @throws InvalidActionNameException
     * @throws InvalidActionVisibilityException
     */
    protected function resolveActionMethodName(ActionRequest $request): string
    {
        if ($request->getControllerActionName() === 'index') {
            $actionName = 'index';
            switch ($request->getHttpRequest()->getMethod()) {
                case 'HEAD':
                case 'GET':
                    $actionName = ($request->hasArgument($this->resourceArgumentName)) ? 'show' : 'list';
                    break;
                case 'POST':
                    $actionName = 'create';
                    break;
                case 'PUT':
                    if (!$request->hasArgument($this->resourceArgumentName)) {
                        $this->throwStatus(400, null, 'No resource specified');
                    }
                    $actionName = 'update';
                    break;
                case 'DELETE':
                    if (!$request->hasArgument($this->resourceArgumentName)) {
                        $this->throwStatus(400, null, 'No resource specified');
                    }
                    $actionName = 'delete';
                    break;
            }
            if ($request->getControllerActionName() !== $actionName) {
                // Clone the request, because it should not be mutated to prevent unexpected routing behavior
                $request = clone $request;
                $request->setControllerActionName($actionName);
            }
        }
        return parent::resolveActionMethodName($request);
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
     * Redirects to another URI
     *
     * @param UriInterface|string $uri Either a string or a psr uri
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @throws StopActionException
     * @api
     */
    protected function redirectToUri(string|UriInterface $uri, int $delay = 0, int $statusCode = 303): never
    {
        if (!$uri instanceof UriInterface) {
            $uri = new Uri($uri);
        }

        $response = $this->responseRedirectsToUri($uri, $delay, $statusCode, $this->response);
        if ($this->request->getFormat() === 'json') {
            // send empty body on redirects for JSON requests
            $response->setContent('');
        }

        $this->throwStopActionWithResponse($response, '');
    }
}
