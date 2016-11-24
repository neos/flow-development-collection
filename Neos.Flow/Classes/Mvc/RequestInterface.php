<?php
namespace Neos\Flow\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for a dispatchable request.
 *
 * @api
 */
interface RequestInterface
{
    /**
     * Sets the dispatched flag
     *
     * @param boolean $flag If this request has been dispatched
     * @return void
     * @api
     */
    public function setDispatched($flag);

    /**
     * If this request has been dispatched and addressed by the responsible
     * controller and the response is ready to be sent.
     *
     * The dispatcher will try to dispatch the request again if it has not been
     * addressed yet.
     *
     * @return boolean TRUE if this request has been dispatched successfully
     * @api
     */
    public function isDispatched();

    /**
     * Returns the object name of the controller which is supposed to process the
     * request.
     *
     * @return string The controller's object name
     * @api
     */
    public function getControllerObjectName();

    /**
     * Returns the top level Request: the one just below the HTTP request
     *
     * @return RequestInterface
     * @api
     */
    public function getMainRequest();

    /**
     * Checks if this request is the uppermost ActionRequest, just one below the
     * HTTP request.
     *
     * @return boolean
     * @api
     */
    public function isMainRequest();
}
