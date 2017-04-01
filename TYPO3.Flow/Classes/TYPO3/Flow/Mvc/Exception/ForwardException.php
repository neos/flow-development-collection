<?php
namespace TYPO3\Flow\Mvc\Exception;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * This exception is thrown by a controller to stop the execution of the current
 * action and return the control to the dispatcher for the special case of a
 * forward().
 *
 * @api
 */
class ForwardException extends StopActionException
{
    /**
     * @var \TYPO3\Flow\Mvc\ActionRequest
     */
    protected $nextRequest;

    /**
     * Sets the next request, containing the information about the next action to
     * execute.
     *
     * @param \TYPO3\Flow\Mvc\ActionRequest $nextRequest
     * @return void
     */
    public function setNextRequest(\TYPO3\Flow\Mvc\ActionRequest $nextRequest)
    {
        $this->nextRequest = $nextRequest;
    }

    /**
     * Returns the next request
     *
     * @return \TYPO3\Flow\Mvc\ActionRequest
     */
    public function getNextRequest()
    {
        return $this->nextRequest;
    }
}
