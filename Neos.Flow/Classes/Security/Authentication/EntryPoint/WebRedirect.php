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

use function GuzzleHttp\Psr7\stream_for;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Security\Exception\MissingConfigurationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * An authentication entry point, that redirects to another webpage.
 */
class WebRedirect extends AbstractEntryPoint
{
    /**
     * @Flow\Inject(lazy = false)
     * @Flow\Transient
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @Flow\Transient
     * @var BaseUriProvider
     */
    protected $baseUriProvider;

    /**
     * Starts the authentication: Redirect to login page
     *
     * @param ServerRequestInterface $request The current request
     * @param ResponseInterface $response The current response
     * @return ResponseInterface
     * @throws MissingConfigurationException
     */
    public function startAuthentication(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uri = null;

        if (isset($this->options['uri'])) {
            $uri = strpos($this->options['uri'], '://') !== false ? $this->options['uri'] : (string)$this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest() . $this->options['uri'];
        }

        if (isset($this->options['routeValues'])) {
            $routeValues = $this->options['routeValues'];
            if (!is_array($routeValues)) {
                throw new MissingConfigurationException(sprintf('The configuration for the WebRedirect authentication entry point is incorrect. "routeValues" must be an array, got "%s".', gettype($routeValues)), 1345040415);
            }

            $uri = $this->generateUriFromRouteValues($this->options['routeValues'], $request);
        }

        if ($uri === null) {
            throw new MissingConfigurationException('The configuration for the WebRedirect authentication entry point is incorrect or missing. You need to specify either the target "uri" or "routeValues".', 1237282583);
        }

        return $response
            ->withBody(stream_for(sprintf('<html><head><meta http-equiv="refresh" content="0;url=%s"/></head></html>', htmlentities($uri, ENT_QUOTES, 'utf-8'))))
            ->withStatus(303)
            ->withHeader('Location', $uri);
    }

    /**
     * @param array $routeValues
     * @param ServerRequestInterface $request
     * @return string
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    protected function generateUriFromRouteValues(array $routeValues, ServerRequestInterface $request): string
    {
        $actionRequest = ActionRequest::fromHttpRequest($request);
        $this->uriBuilder->setRequest($actionRequest);

        $actionName = $this->extractRouteValue($routeValues, '@action');
        $controllerName = $this->extractRouteValue($routeValues, '@controller');
        $packageKey = $this->extractRouteValue($routeValues, '@package');
        $subPackageKey = $this->extractRouteValue($routeValues, '@subpackage');
        return $this->uriBuilder->setCreateAbsoluteUri(true)->uriFor($actionName, $routeValues, $controllerName, $packageKey, $subPackageKey);
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
