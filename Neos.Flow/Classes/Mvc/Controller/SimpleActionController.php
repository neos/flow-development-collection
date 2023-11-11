<?php
namespace Neos\Flow\Mvc\Controller;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\SignalSlot\Exception\InvalidSlotException;

/**
 * Provides most direct access to our request/response abstraction,
 * the request comes directly from the dispatcher and goes directly back to it.
 *
 * Views or other processing needs to be added to your controller as needed.
 * For helpers to facilitate throws, forwards, redirects and such:
 * @see SpecialResponsesSupport
 */
class SimpleActionController implements ControllerInterface
{
    /**
     * @param ActionRequest $request
     * @return ActionResponse
     * @throws NoSuchActionException
     * @throws InvalidSlotException
     */
    public function processRequest(ActionRequest $request): ActionResponse
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
    protected function resolveActionMethodName(ActionRequest $request)
    {
        $actionMethodName = $request->getControllerActionName() . 'Action';
        if (!is_callable([$this, $actionMethodName])) {
            throw new NoSuchActionException(sprintf('An action "%s" does not exist in controller "%s".', $actionMethodName, get_class($this)), 1186669086);
        }

        return $actionMethodName;
    }
}
