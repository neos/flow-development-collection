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
 * See the Action Controller's forward() and redirectToUri() methods for more information.
 *
 * @api
 */
final class StopActionException extends \Neos\Flow\Mvc\Exception
{
    /**
     * As throwing the exception allows for an unusual control flow, we attach the response for the dispatcher.
     */
    public readonly ActionResponse $response;

    private function __construct(string $message, int $code, ?\Throwable $previous, ActionResponse $response)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public static function create(
        ActionResponse $response,
        string $message
    ) {
        return new self($message, 1558088618, null, $response);
    }
}
