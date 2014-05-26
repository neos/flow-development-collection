<?php
namespace TYPO3\Flow\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for a dispatchable request.
 *
 * @api
 */
interface RequestInterface {

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
	 * @return \TYPO3\Flow\Mvc\RequestInterface
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
