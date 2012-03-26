<?php
namespace TYPO3\FLOW3\Security\Authentication;

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
 * Contract for an authentication entry point
 */
interface EntryPointInterface {

	/**
	 * Returns TRUE if the given request can be authenticated by the authentication provider
	 * represented by this entry point
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request The current request
	 * @return boolean TRUE if authentication is possible
	 */
	public function canForward(\TYPO3\FLOW3\Mvc\RequestInterface $request);

	/**
	 * Sets the options array
	 *
	 * @param array $options An array of configuration options
	 * @return void
	 */
	public function setOptions(array $options);

	/**
	 * Returns the options array
	 *
	 * @return array An array of configuration options
	 */
	public function getOptions();

	/**
	 * Starts the authentication. (e.g. redirect to login page or send 401 HTTP header)
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The current request
	 * @param \TYPO3\FLOW3\MVC\ResponseInterface $response The current response
	 * @return void
	 */
	public function startAuthentication(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\ResponseInterface $response);

}
?>