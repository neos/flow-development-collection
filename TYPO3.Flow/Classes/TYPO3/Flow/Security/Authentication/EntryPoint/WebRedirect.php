<?php
namespace TYPO3\Flow\Security\Authentication\EntryPoint;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;

/**
 * An authentication entry point, that redirects to another webpage.
 */
class WebRedirect extends AbstractEntryPoint {

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * Starts the authentication: Redirect to login page
	 *
	 * @param \TYPO3\Flow\Http\Request $request The current request
	 * @param \TYPO3\Flow\Http\Response $response The current response
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\MissingConfigurationException
	 */
	public function startAuthentication(Request $request, Response $response) {
		if (isset($this->options['routeValues'])) {
			$routeValues = $this->options['routeValues'];
			if (!is_array($routeValues)) {
				throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException(sprintf('The configuration for the WebRedirect authentication entry point is incorrect. "routeValues" must be an array, got "%s".', gettype($routeValues)), 1345040415);
			}
			$actionRequest = $request->createActionRequest();
			$this->uriBuilder->setRequest($actionRequest);

			$actionName = $this->extractRouteValue($routeValues, '@action');
			$controllerName = $this->extractRouteValue($routeValues, '@controller');
			$packageKey = $this->extractRouteValue($routeValues, '@package');
			$subPackageKey = $this->extractRouteValue($routeValues, '@subpackage');
			$uri = $this->uriBuilder->setCreateAbsoluteUri(TRUE)->uriFor($actionName, $routeValues, $controllerName, $packageKey, $subPackageKey);
		} elseif (isset($this->options['uri'])) {
			$uri = (strpos('://', $this->options['uri'] !== FALSE)) ? $this->options['uri'] : $request->getBaseUri() . $this->options['uri'];
		} else {
			throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('The configuration for the WebRedirect authentication entry point is incorrect or missing. You need to specify either the target "uri" or "routeValues".', 1237282583);
		}

		$response->setContent(sprintf('<html><head><meta http-equiv="refresh" content="0;url=%s"/></head></html>', htmlentities($uri, ENT_QUOTES, 'utf-8')));
		$response->setStatus(303);
		$response->setHeader('Location', $uri);
	}

	/**
	 * Returns the entry $key from the array $routeValues removing the original array item.
	 * If $key does not exist, NULL is returned.
	 *
	 * @param array $routeValues
	 * @param string $key
	 * @return mixed the specified route value or NULL if it is not set
	 */
	protected function extractRouteValue(array &$routeValues, $key) {
		if (!isset($routeValues[$key])) {
			return NULL;
		}
		$routeValue = $routeValues[$key];
		unset($routeValues[$key]);
		return $routeValue;
	}

}
