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
 * An authentication entry point, that redirects to another webpage.
 *
 */
class WebRedirect implements \TYPO3\FLOW3\Security\Authentication\EntryPointInterface {

	/**
	 * The configurations options
	 * @var array
	 */
	protected $options = array();

	/**
	 * Returns TRUE if the given request can be authenticated by the authentication provider
	 * represented by this entry point
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The current request
	 * @return boolean TRUE if authentication is possible
	 */
	public function canForward(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		return ($request instanceof \TYPO3\FLOW3\MVC\Web\Request);
	}

	/**
	 * Sets the options array
	 *
	 * @param array $options An array of configuration options
	 * @return void
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Returns the options array
	 *
	 * @return array The configuration options of this entry point
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Starts the authentication: Redirect to login page
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The current request
	 * @param \TYPO3\FLOW3\MVC\ResponseInterface $response The current response
	 * @return void
	 */
	public function startAuthentication(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\ResponseInterface $response) {
		if (!$this->canForward($request)) throw new \TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException('Unsupported request type for authentication entry point given.', 1237282462);
		if (!is_array($this->options) || !isset($this->options['uri'])) throw new \TYPO3\FLOW3\Security\Exception\MissingConfigurationException('The configuration for the WebRedirect authentication entry point is incorrect or missing.', 1237282583);

		$plainUri = (strpos('://', $this->options['uri'] !== FALSE)) ? $this->options['uri'] : $request->getBaseUri() . $this->options['uri'];
		$escapedUri = htmlentities($plainUri, ENT_QUOTES, 'utf-8');

		$response->setContent('<html><head><meta http-equiv="refresh" content="0;url=' . $escapedUri . '"/></head></html>');
		$response->setStatus(303);
		$response->setHeader('Location', $plainUri);

	}
}

?>