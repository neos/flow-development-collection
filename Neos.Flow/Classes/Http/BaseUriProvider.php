<?php
namespace Neos\Flow\Http;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Helper\RequestInformationHelper;
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
     * @Flow\InjectConfiguration(package="Neos.Flow" path="http.baseUri")
     * @var string|null
     */
    protected $configuredBaseUri;

    /**
     * A baseUri set during runtime.
     *
     * @var UriInterface|null
     */
    protected $runtimeBaseUri;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Get a possible base URI set during runtime.
     *
     * @return UriInterface|null
     */
    public function getRuntimeBaseUri(): ?UriInterface
    {
        return $this->runtimeBaseUri;
    }

    /**
     * Set a base URI during runtime.
     * Th This is meant as extension point if you have your own way of figuring out the baseUri.
     *
     * @param UriInterface $runtimeBaseUri
     * @return void
     */
    public function setRuntimeBaseUri(UriInterface $runtimeBaseUri): void
    {
        $this->runtimeBaseUri = $runtimeBaseUri;
    }

    /**
     * Get the configured framework base URI.
     *
     * @return Uri|null
     */
    public function getConfiguredBaseUri(): ?UriInterface
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
    public function generateBaseUriFromHttpRequest(): ?UriInterface
    {
        $activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$activeRequestHandler instanceof HttpRequestHandlerInterface) {
            return null;
        }

        $componentContext = $activeRequestHandler->getComponentContext();
        return RequestInformationHelper::generateBaseUri($componentContext->getHttpRequest());
    }

    /**
     * Gives the best possible base URI with the following priority:
     * - runtime base URI
     * - configured base URI
     * - generated base URI from request
     *
     * To ensure a base URI can always be provided this will throw an
     * exception if none of the three options yields a result.
     *
     * @return UriInterface
     * @throws Exception
     */
    public function getBestPossibleBaseUri(): UriInterface
    {
        $baseUri = $this->getRuntimeBaseUri();
        if ($baseUri instanceof UriInterface) {
            return $baseUri;
        }

        $baseUri = $this->getConfiguredBaseUri();
        if ($baseUri instanceof UriInterface) {
            return $baseUri;
        }

        $baseUri = $this->generateBaseUriFromHttpRequest();
        if ($baseUri instanceof UriInterface) {
            return $baseUri;
        }

        throw new Exception('No base URI could be provided. This probably means a call was made outside of an HTTP request and a base URI was neither configured nor set during runtime.', 1567529953);
    }
}
