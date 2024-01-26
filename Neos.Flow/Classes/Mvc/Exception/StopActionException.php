<?php
namespace Neos\Flow\Mvc\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionResponse;

/**
 * This exception is thrown by a controller to stop the execution of the current
 * action and return the control to the dispatcher. The dispatcher catches this
 * exception and - depending on the "dispatched" status of the request - either
 * continues dispatching the request or returns control to the request handler.
 *
 * See {@see SpecialResponsesSupport::throwStopActionWithResponse()}
 * or {@see SpecialResponsesSupport::responseRedirectsToUri()} for more information.
 *
 * Other control flow exceptions: {@see ForwardException}
 *
 * @api
 */
final class StopActionException extends \Neos\Flow\Mvc\Exception
{
    /**
     * The response to be received by the MVC Dispatcher.
     */
    public readonly ActionResponse $response;

    private function __construct(string $message, int $code, ?\Throwable $previous, ActionResponse $response)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * @param ActionResponse $response The response to be received by the MVC Dispatcher.
     * @param string $details Additional details just for this exception, in case it is logged (the regular exception message).
     */
    public static function createForResponse(ActionResponse $response, string $details): self
    {
        if (empty($details)) {
            $details = sprintf(
                'Stop action with %s response.',
                $response->getStatusCode()
            );
        }
        return new self($details, 1558088618, null, $response);
    }
}
