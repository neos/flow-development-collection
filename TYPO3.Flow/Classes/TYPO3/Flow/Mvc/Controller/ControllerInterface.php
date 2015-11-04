<?php
namespace TYPO3\Flow\Mvc\Controller;

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
 * Generic interface for controllers
 *
 * This interface serves as a common contract for all kinds of controllers. That is,
 * in Flow it covers ActionController (dealing with ActionRequest) but also
 * CommandController (dealing with CommandRequest).
 *
 * Controllers implementing this interface are compatible with the MVC Dispatcher.
 *
 * @api
 */
interface ControllerInterface
{
    /**
     * Processes a general request. The result can be returned by altering the given response.
     *
     * @param \TYPO3\Flow\Mvc\RequestInterface $request The request object
     * @param \TYPO3\Flow\Mvc\ResponseInterface $response The response, modified by the controller
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
     * @api
     */
    public function processRequest(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response);
}
