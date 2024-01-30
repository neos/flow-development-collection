<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc\Controller;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\NoSuchActionException;

/**
 * Helps to implement a controller with direct access to flows request/response abstraction.
 *
 * ```
 * ┌─────────────────────────────────────────────────────────────────────────────┐
 * │ class MyController implements ControllerInterface                           │
 * │ {                                                                           │
 * │     use ActionToMethodDelegation;                                           │
 * │     public function myAction(ActionRequest $actionRequest): ActionResponse; │
 * │ }                                                                           │
 * └─────────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * The request comes directly from the dispatcher and goes directly back to it.
 *
 * For helpers to facilitate throws, forwards, redirects: {@see SpecialResponsesSupport}
 *
 * Views or other processing needs to be added to your controller as needed,
 * helpers will be suggested here as they become available.
 * @api
 */
trait ActionToMethodDelegation
{
    /**
     * @internal you don't need to use this trait if you need to override this functionality.
     */
    final public function processRequest(ActionRequest $request): ActionResponse
    {
        $request->setDispatched(true);
        $actionMethodName = $this->resolveActionMethodName($request);
        return $this->$actionMethodName($request);
    }

    /**
     * Resolves and checks the current action method name
     *
     * @return string Method name of the current action
     * @throws NoSuchActionException
     */
    private function resolveActionMethodName(ActionRequest $request): string
    {
        $actionMethodName = $request->getControllerActionName() . 'Action';
        if (!is_callable([$this, $actionMethodName])) {
            throw new NoSuchActionException(sprintf('An action "%s" does not exist in controller "%s".', $actionMethodName, get_class($this)), 1186669086);
        }

        return $actionMethodName;
    }
}
