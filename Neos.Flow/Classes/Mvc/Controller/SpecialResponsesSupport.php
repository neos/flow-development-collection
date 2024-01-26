<?php
namespace Neos\Flow\Mvc\Controller;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Psr\Http\Message\UriInterface;

/**
 * provides helper methods to facilitate redirects, status throws, stop action and forwards
 */
trait SpecialResponsesSupport
{
    /**
     * Sends the specified HTTP status immediately.
     *
     * @param integer $statusCode The HTTP status code
     * @param string $content Body content which further explains the status the body of a given response will be overwritten if this is not empty
     * @param ActionResponse|null $response The response to use or null for an empty response with the given status and message or content
     * @return never
     * @throws StopActionException
     */
    protected function responseThrowsStatus(int $statusCode, string $content = '', ?ActionResponse $response = null): never
    {
        $response = $response ?? new ActionResponse;

        $response->setStatusCode($statusCode);
        if ($content !== '') {
            $response->setContent($content);
        }

        $this->throwStopActionWithResponse($response, $content);
    }

    /**
     * Redirects to another URI
     *
     * @param UriInterface $uri Either a string representation of a URI or a UriInterface object
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param ActionResponse|null $response response that will have status, and location or body overwritten.
     * @return ActionResponse
     * @throws StopActionException
     */
    protected function responseRedirectsToUri(UriInterface $uri, int $delay = 0, int $statusCode = 303, ?ActionResponse $response = null): ActionResponse
    {
        $nextResponse = $response !== null ? clone $response : new ActionResponse();

        if ($delay < 1) {
            $nextResponse->setRedirectUri($uri, $statusCode);
            $this->throwStopActionWithResponse($nextResponse, '');
        }

        $nextResponse->setStatusCode($statusCode);
        $content = sprintf('<html><head><meta http-equiv="refresh" content="%u;url=%s"/></head></html>', $delay, $uri);
        $nextResponse->setContent($content);
        return $nextResponse;
    }

    /**
     * @param ActionResponse $response The response to be received by the MVC Dispatcher.
     * @param string $details Additional details just for the exception, in case it is logged (the regular exception message).
     * @return never
     * @throws StopActionException
     */
    protected function throwStopActionWithResponse(ActionResponse $response, string $details = ''): never
    {
        throw StopActionException::create($response, $details);
    }

    /**
     * Forwards the request to another action and / or controller
     * Request is directly transferred to the other action / controller
     *
     * NOTE that this will not try to convert any objects in the requests arguments,
     * this can be a fine or a problem depending on context of usage.
     *
     * @param ActionRequest $request The request to redirect to
     * @return never
     * @throws ForwardException
     */
    protected function forwardToRequest(ActionRequest $request): never
    {
        $nextRequest = clone $request;
        throw ForwardException::create($nextRequest, '');
    }
}
