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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\StopActionException;

/**
 * Generic interface for controllers
 *
 * This interface serves as a common contract for all kinds of controllers. That is,
 * in Flow it covers typical ActionController scenarios. They deal with an incoming
 * request and provide a response.
 *
 * Controllers implementing this interface are compatible with the MVC Dispatcher.
 *
 * @api
 */
interface ControllerInterface
{
    /**
     * Processes a general request.
     *
     * The action request can either be returned, or handed over
     * by throwing a dedicated exception with response attached.
     *
     * @param ActionRequest $request The dispatched action request
     * @return ActionResponse The resulting created response
     * @throws StopActionException is allowed for exceptional control flow
     * @throws ForwardException is allowed for exceptional control flow
     * @api
     */
    public function processRequest(ActionRequest $request): ActionResponse;
}
