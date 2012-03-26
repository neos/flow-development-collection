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

/**
 * An authentication entry point, that sends an HTTP header to start HTTP Basic authentication.
 */
class HttpBasic extends \TYPO3\FLOW3\Security\Authentication\EntryPoint\AbstractEntryPoint {

	/**
	 * Starts the authentication: Send HTTP header
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request The current request
	 * @param \TYPO3\FLOW3\Mvc\ResponseInterface $response The current response
	 * @return void
	 */
	public function startAuthentication(\TYPO3\FLOW3\Mvc\RequestInterface $request, \TYPO3\FLOW3\Mvc\ResponseInterface $response) {
		if (!$this->canForward($request)) throw new \TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException('Unsupported request type for authentication entry point given.', 1237282465);
		$response->setStatus(401);
		$response->setHeader('WWW-Authenticate', 'Basic realm="' . (isset($this->options['realm']) ? $this->options['realm'] : 'Authentication required!') . '"');
		$response->setContent('Authorization required!');
	}

}
?>
