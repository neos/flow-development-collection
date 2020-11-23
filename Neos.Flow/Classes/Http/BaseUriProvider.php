<?php
namespace Neos\Flow\Http;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Supports to get a baseUri from various possible sources.
 *
 * @Flow\Scope("singleton")
 */
class BaseUriProvider
{
    /**
     * THe possibly configured Flow base URI.
     *
     * @Flow\InjectConfiguration(package="Neos.Flow", path="http.baseUri")
     * @var string|null
     */
    protected $configuredBaseUri;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Get the configured framework base URI.
     *
     * @return Uri|null
     */
    private function getConfiguredBaseUri(): ?UriInterface
    {
        if ($this->configuredBaseUri === null) {
            return null;
        }

        return new Uri($this->configuredBaseUri);
    }

    /**
     * Generates a base URI from the currently active HTTP request.
     * Note that we cannot actually know the base URI if your installation
     * is in a sub directory, so in that case this will probably result in
     * a faulty base URI.
     *
     * @return UriInterface|null
     */
    private function generateBaseUriFromHttpRequest(): ?UriInterface
    {
        $activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$activeRequestHandler instanceof HttpRequestHandlerInterface) {
            return null;
        }

        $request = $activeRequestHandler->getHttpRequest();
        return RequestInformationHelper::generateBaseUri($request);
    }

    /**
     * Gives the best possible base URI with the following priority:
     * - configured base URI
     * - generated base URI from currently active server request
     * - generated base URI from specified $fallbackRequest
     *
     * To ensure a base URI can always be provided this will throw an
     * exception if none of the options yields a result.
     *
     * @param ServerRequestInterface|null $fallbackRequest HTTP request to be used if no base Uri is configured and the currently active request can't be determined (e.g. in CLI context)
     * @return UriInterface
     * @throws Exception
     */
    public function getConfiguredBaseUriOrFallbackToCurrentRequest(ServerRequestInterface $fallbackRequest = null): UriInterface
    {
        $baseUri = $this->getConfiguredBaseUri();
        if ($baseUri instanceof UriInterface) {
            return $baseUri;
        }

        $baseUri = $this->generateBaseUriFromHttpRequest();
        if ($baseUri instanceof UriInterface) {
            return $baseUri;
        }

        if ($fallbackRequest !== null) {
            return RequestInformationHelper::generateBaseUri($fallbackRequest);
        }

        throw new Exception('No base URI could be provided. This probably means a call was made outside of an HTTP request and a base URI was neither configured nor specified as $fallbackRequest.', 1567529953);
    }
}
