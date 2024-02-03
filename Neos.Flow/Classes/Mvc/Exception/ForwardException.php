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
use Neos\Flow\Mvc\ActionRequest;

/**
 * This exception is thrown by a controller to stop the execution of the current
 * action and return the control to the dispatcher for the special case of a forward.
 *
 * See {@see SpecialResponsesSupport::forwardToRequest()} for more information.
 *
 * Other control flow exceptions: {@see StopActionException}
 *
 * @api
 */
final class ForwardException extends \Neos\Flow\Mvc\Exception
{
    /**
     * The next request the MVC Dispatcher should handle.
     */
    public readonly ActionRequest $nextRequest;

    private function __construct(string $message, int $code, ?\Throwable $previous, ActionRequest $nextRequest)
    {
        parent::__construct($message, $code, $previous);
        $this->nextRequest = $nextRequest;
    }

    /**
     * @param ActionRequest $nextRequest The next request the MVC Dispatcher should handle.
     * @param string $details Additional details just for this exception, in case it is logged (the regular exception message).
     */
    public static function createForNextRequest(ActionRequest $nextRequest, string $details): self
    {
        if (empty($details)) {
            $details = sprintf(
                'Forward action to %s controller.',
                $nextRequest->getControllerObjectName()
            );
        }
        return new self($details, 1706272103, null, $nextRequest);
    }
}
