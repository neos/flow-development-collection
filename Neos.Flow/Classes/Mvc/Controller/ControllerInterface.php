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
     * The contract to the MVC Dispatcher is as follows.
     *
     * - If a request can be handled it must be set to `dispatched` {@see ActionRequest::setDispatched()}
     * - The repose should be returned directly.
     * - For outer ordinary control flow a {@see StopActionException} with response attached
     * can be thrown which will be handled by the Dispatcher accordingly.
     *
     * - To forward the request to another controller, a {@see ForwardException} might be thrown
     * wich the Dispatcher will catch and handle its attached next-request.
     *
     * @param ActionRequest $request The dispatched action request
     * @return ActionResponse The resulting created response
     * @throws StopActionException
     * @throws ForwardException
     * @api
     */
    public function processRequest(ActionRequest $request): ActionResponse;
}
