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
 * action and return the control to the dispatcher for the special case of a
 * forward().
 *
 * @api
 */
final class ForwardException extends \Neos\Flow\Mvc\Exception
{
    /**
     * @var ActionRequest The next request, containing the information about the next action to execute.
     */
    public readonly ActionRequest $nextRequest;

    private function __construct(string $message, int $code, ?\Throwable $previous, ActionRequest $nextRequest)
    {
        parent::__construct($message, $code, $previous);
        $this->nextRequest = $nextRequest;
    }

    public static function create(ActionRequest $nextRequest)
    {
        return new self('', 0, null, $nextRequest);
    }
}
