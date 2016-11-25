<?php
namespace Neos\Flow\Security\Authentication\EntryPoint;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Security\Exception\MissingConfigurationException;

/**
 * An authentication entry point, that redirects to another webpage.
 */
class WebRedirect extends AbstractEntryPoint
{
    /**
     * @Flow\Inject(lazy = FALSE)
     * @Flow\Transient
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Starts the authentication: Redirect to login page
     *
     * @param Request $request The current request
     * @param Response $response The current response
     * @return void
     * @throws MissingConfigurationException
     */
    public function startAuthentication(Request $request, Response $response)
    {
        if (isset($this->options['routeValues'])) {
            $routeValues = $this->options['routeValues'];
            if (!is_array($routeValues)) {
                throw new MissingConfigurationException(sprintf('The configuration for the WebRedirect authentication entry point is incorrect. "routeValues" must be an array, got "%s".', gettype($routeValues)), 1345040415);
            }
            $actionRequest = new ActionRequest($request);
            $this->uriBuilder->setRequest($actionRequest);

            $actionName = $this->extractRouteValue($routeValues, '@action');
            $controllerName = $this->extractRouteValue($routeValues, '@controller');
            $packageKey = $this->extractRouteValue($routeValues, '@package');
            $subPackageKey = $this->extractRouteValue($routeValues, '@subpackage');
            $uri = $this->uriBuilder->setCreateAbsoluteUri(true)->uriFor($actionName, $routeValues, $controllerName, $packageKey, $subPackageKey);
        } elseif (isset($this->options['uri'])) {
            $uri = strpos($this->options['uri'], '://') !== false ? $this->options['uri'] : $request->getBaseUri() . $this->options['uri'];
        } else {
            throw new MissingConfigurationException('The configuration for the WebRedirect authentication entry point is incorrect or missing. You need to specify either the target "uri" or "routeValues".', 1237282583);
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
    protected function extractRouteValue(array &$routeValues, $key)
    {
        if (!isset($routeValues[$key])) {
            return null;
        }
        $routeValue = $routeValues[$key];
        unset($routeValues[$key]);
        return $routeValue;
    }
}
