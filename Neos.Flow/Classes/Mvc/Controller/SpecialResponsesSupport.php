<?php
namespace Neos\Flow\Mvc\Controller;

use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
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
    protected function reponseThrowsStatus(int $statusCode, string $content = '', ?ActionResponse $response =  null): never
    {
        $response = $response ?? new ActionResponse;

        $response->setStatusCode($statusCode);
        if ($content !== '') {
            $response->setContent($content);
        }

        $this->throwStopActionWithReponse($response, $content, 1558088618);
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
            $this->throwStopActionWithReponse($nextResponse, '', 1699478812);
        }

        $nextResponse->setStatusCode($statusCode);
        $content = sprintf('<html><head><meta http-equiv="refresh" content="%u;url=%s"/></head></html>', $delay, $uri);
        $nextResponse->setContent($content);
        return $nextResponse;
    }

    /**
     * @param ActionResponse $response
     * @param string $message
     * @param int $code
     * @return never
     * @throws StopActionException
     */
    protected function throwStopActionWithReponse(ActionResponse $response, string $message = '', int $code = 0): never
    {
        $exception = new StopActionException($message, $code);
        $exception->response = $response;
        throw $exception;
    }

    /**
     * Forwards the request to another action and / or controller
     * Request is directly transfered to the other action / controller
     *
     * NOTE that this will not try to convert any objects in the requests arguments,
     * this can be a fine or a problem depending on context of usage.
     *
     * @param ActionRequest $request The request to redirect to
     * @return never
     * @throws ForwardException
     */
    protected function forwardToRequset(ActionRequest $request): never
    {
        $nextRequest = clone $request;
        $forwardException = new ForwardException();
        $forwardException->setNextRequest($nextRequest);
        throw $forwardException;
    }
}
