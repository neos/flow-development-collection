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
 * An authentication entry point, that redirects to another webpage.
 */
class WebRedirect extends AbstractEntryPoint {

	/**
	 * Starts the authentication: Redirect to login page
	 *
	 * @param \TYPO3\FLOW3\Http\Request $request The current request
	 * @param \TYPO3\FLOW3\Http\Response $response The current response
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 * @throws \TYPO3\FLOW3\Security\Exception\MissingConfigurationException
	 */
	public function startAuthentication(Request $request, Response $response) {
		if (!isset($this->options['uri'])) {
			throw new \TYPO3\FLOW3\Security\Exception\MissingConfigurationException('The configuration for the WebRedirect authentication entry point is incorrect or missing.', 1237282583);
		}

		$plainUri = (strpos('://', $this->options['uri'] !== FALSE)) ? $this->options['uri'] : $request->getBaseUri() . $this->options['uri'];
		$escapedUri = htmlentities($plainUri, ENT_QUOTES, 'utf-8');

		$response->setContent('<html><head><meta http-equiv="refresh" content="0;url=' . $escapedUri . '"/></head></html>');
		$response->setStatus(303);
		$response->setHeader('Location', $plainUri);
	}
}

?>
