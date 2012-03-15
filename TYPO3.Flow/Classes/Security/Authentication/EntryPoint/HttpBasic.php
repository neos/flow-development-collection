<?php
namespace TYPO3\FLOW3\Security\Authentication\EntryPoint;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Response;

/**
 * An authentication entry point, that sends an HTTP header to start HTTP Basic authentication.
 */
class HttpBasic extends AbstractEntryPoint {

	/**
	 * Starts the authentication: Send HTTP header
	 *
	 * @param \TYPO3\FLOW3\Http\Request $request The current request
	 * @param \TYPO3\FLOW3\Http\Response $response The current response
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 */
	public function startAuthentication(Request $request, Response $response) {
		$response->setStatus(401);
		$response->setHeader('WWW-Authenticate', 'Basic realm="' . (isset($this->options['realm']) ? $this->options['realm'] : sha1(FLOW3_PATH_ROOT)) . '"');
		$response->setContent('Authorization required');
	}

}
?>
