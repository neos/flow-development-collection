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
use Neos\Flow\Http\Component\ReplaceHttpResponseComponent;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\InvalidActionVisibilityException;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Psr\Http\Message\ResponseInterface;

/**
 * An HTTP based multi-action controller.
 *
 * The action specified in the given ActionRequest is dispatched to a method in
 * the concrete controller whose name ends with "*Action". If no matching action
 * method is found, the action specified in $errorMethodName is invoked.
 *
 * This controller does not apply any mapping and istead passes the request as argument
 * to the action and expects an ActionResponse or a Psr7/ResponseInterface in return.
 */
class HttpController extends AbstractController
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Name of the action method
     *
     * @var string
     */
    protected $actionMethodName;

    /**
     * Handles a request. The result output is returned by altering the given response.
     *
     * @param ActionRequest $httpRequest The request object
     * @param ActionResponse $response The response, modified by this handler
     * @return void
     * @api
     */
    public function processRequest(ActionRequest $request, ActionResponse $response)
    {
        $this->initializeController($request, $response);

        $actionMethodName = $this->resolveActionMethoName();
        $actionResponse = call_user_func_array([$this, $actionMethodName], [$request]);

        if ($actionResponse instanceof ActionResponse) {
            $actionResponse->mergeIntoParentResponse($response);
        } elseif ($actionResponse instanceof ResponseInterface) {
            $response->setComponentParameter(ReplaceHttpResponseComponent::class, ReplaceHttpResponseComponent::PARAMETER_RESPONSE, $actionResponse);
        } elseif (is_string($actionResponse)) {
            $response->setContent($actionResponse);
        } else {
            throw new \UnexpectedValueException('The action response has return a PSR7 response or a string');
        }
    }

    /**
     * Resolves and checks the current action method name
     */
    protected function resolveActionMethoName()
    {
        $actionMethodName = $this->request->getControllerActionName() . 'Action';
        if (!is_callable([$this, $actionMethodName])) {
            throw new NoSuchActionException(sprintf('An action "%s" does not exist in controller "%s".', $actionMethodName, get_class($this)), 1186669086);
        }
        $publicActionMethods = static::getPublicActionMethods($this->objectManager);
        if (!isset($publicActionMethods[$actionMethodName])) {
            throw new InvalidActionVisibilityException(sprintf('The action "%s" in controller "%s" is not public!', $actionMethodName, get_class($this)), 1186669086);
        }
        return $actionMethodName;
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array Array of all public action method names, indexed by method name
     * @Flow\CompileStatic
     */
    public static function getPublicActionMethods($objectManager)
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);

        $result = [];

        $className = get_called_class();
        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if (strlen($methodName) > 6 && strpos($methodName, 'Action', strlen($methodName) - 6) !== false) {
                if ($reflectionService->isMethodPublic($className, $methodName)) {
                    $result[$methodName] = true;
                }
            }
        }
        return $result;
    }
}
