<?php
namespace TYPO3\Flow\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Core\RequestHandlerInterface;

/**
 * The interface for a request handler which handles and works with HTTP requests
 *
 * @api
 */
interface HttpRequestHandlerInterface extends RequestHandlerInterface {

	/**
	 * Returns the currently processed HTTP request
	 *
	 * @return \TYPO3\Flow\Http\Request
	 * @api
	 */
	public function getHttpRequest();

	/**
	 * Returns the HTTP response corresponding to the currently handled request
	 *
	 * @return \TYPO3\Flow\Http\Response
	 * @api
	 */
	public function getHttpResponse();

}
