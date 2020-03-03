<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Http\Headers;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * An HTTP client simulating a web browser
 *
 * @api
 */
class Browser
{
    /**
     * @var RequestInterface
     */
    protected $lastRequest;

    /**
     * @var ResponseInterface
     */
    protected $lastResponse;

    /**
     * If redirects should be followed
     *
     * @var boolean
     */
    protected $followRedirects = true;

    /**
     * The very maximum amount of redirections to follow if there is
     * a "Location" redirect (see also $redirectionStack property)
     *
     * @var integer
     */
    protected $maximumRedirections = 10;

    /**
     * A simple string array that keeps track of occurred "Location" header
     * redirections to avoid infinite loops if the same redirection happens
     *
     * @var array
     */
    protected $redirectionStack = [];

    /**
     * @var Headers
     */
    protected $automaticRequestHeaders;

    /**
     * @var RequestEngineInterface
     */
    protected $requestEngine;

    /**
     * @Flow\Inject
     * @var RequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * @Flow\Inject
     * @var StreamFactoryInterface
     */
    protected $contentStreamFactory;

    /**
     * Construct the Browser instance.
     */
    public function __construct()
    {
        $this->automaticRequestHeaders = new Headers();
    }

    /**
     * Inject the request engine
     *
     * @param RequestEngineInterface $requestEngine
     * @return void
     */
    public function setRequestEngine(RequestEngineInterface $requestEngine): void
    {
        $this->requestEngine = $requestEngine;
    }

    /**
     * Allows to add headers to be sent with every request the browser executes.
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @param array|string|\DateTime $values An array of values or a single value for the specified header field
     * @return void
     * @see Message::setHeader()
     */
    public function addAutomaticRequestHeader($name, $values): void
    {
        $this->automaticRequestHeaders->set($name, $values);
    }

    /**
     * Allows to remove headers that were added with addAutomaticRequestHeader.
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @return void
     */
    public function removeAutomaticRequestHeader($name): void
    {
        $this->automaticRequestHeaders->remove($name);
    }

    /**
     * Requests the given URI with the method and other parameters as specified.
     * If a Location header was given and the status code is of response type 3xx
     * (see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html, 14.30 Location)
     *
     * @param string|UriInterface $uri
     * @param string $method Request method, for example "GET"
     * @param array $arguments Arguments to send in the request body
     * @param UploadedFileInterface[] $files
     * @param string $content
     * @return ResponseInterface The HTTP response
     * @throws \InvalidArgumentException
     * @throws InfiniteRedirectionException
     * @throws HttpException
     * @api
     */
    public function request($uri, $method = 'GET', array $arguments = [], array $files = [], $content = null): ResponseInterface
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        if (!$uri instanceof UriInterface) {
            throw new \InvalidArgumentException('$uri must be a URI object or a valid string representation of a URI.', 1333443624);
        }
        $request = $this->requestFactory->createRequest($method, $uri);
        if ($content) {
            $request = $request->withBody($this->contentStreamFactory->createStream($content));
        }

        if (!empty($arguments)) {
            $request = $request->withQueryParams($arguments);
        }
        if (!empty($files)) {
            $request = $request->withUploadedFiles($files);
        }

        $response = $this->sendRequest($request);

        $location = $response->getHeaderLine('Location');
        if ($this->followRedirects && !empty($location) && $response->getStatusCode() >= 300 && $response->getStatusCode() <= 399) {
            $location = urldecode($location);
            if (strpos($location, '/') === 0) {
                // Location header is a host-absolute URL; so we need to prepend the hostname to create a full URL.
                $location = (string)RequestInformationHelper::generateBaseUri($request) . ltrim($location, '/');
            }

            if (in_array($location, $this->redirectionStack, true) || count($this->redirectionStack) >= $this->maximumRedirections) {
                throw new InfiniteRedirectionException('The Location "' . $location . '" to follow for a redirect will probably result into an infinite loop.', 1350391699);
            }
            $this->redirectionStack[] = $location;
            return $this->request($location);
        }
        $this->redirectionStack = [];

        return $response;
    }

    /**
     * Sets a flag if redirects should be followed or not.
     *
     * @param boolean $flag
     * @return void
     */
    public function setFollowRedirects($flag): void
    {
        $this->followRedirects = (boolean)$flag;
    }

    /**
     * Sends a prepared request and returns the respective response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws HttpException
     * @api
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach ($this->automaticRequestHeaders->getAll() as $name => $values) {
            $request = $request->withAddedHeader($name, $values);
        }

        $this->lastRequest = $request;
        $this->lastResponse = $this->requestEngine->sendRequest($request);
        return $this->lastResponse;
    }

    /**
     * Returns the response received after the last request.
     *
     * @return ResponseInterface The HTTP response or NULL if there wasn't a response yet
     * @api
     */
    public function getLastResponse(): ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * Returns the last request executed.
     *
     * @return RequestInterface The HTTP request or NULL if there wasn't a request yet
     * @api
     */
    public function getLastRequest(): RequestInterface
    {
        return $this->lastRequest;
    }

    /**
     * Returns the request engine used by this Browser.
     *
     * @return RequestEngineInterface
     * @api
     */
    public function getRequestEngine(): RequestEngineInterface
    {
        return $this->requestEngine;
    }

    /**
     * Returns the DOM crawler which can be used to interact with the web page
     * structure, submit forms, click links or fetch specific parts of the
     * website's contents.
     *
     * The returned DOM crawler is bound to the response of the last executed
     * request.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     * @api
     */
    public function getCrawler(): Crawler
    {
        $crawler = new Crawler(null, (string)$this->lastRequest->getUri(), (string)RequestInformationHelper::generateBaseUri($this->lastRequest));
        $this->lastResponse->getBody()->rewind();
        $crawler->addContent($this->lastResponse->getBody()->getContents(), $this->lastResponse->getHeaderLine('Content-Type'));
        $this->lastResponse->getBody()->rewind();

        return $crawler;
    }

    /**
     * Get the form specified by $xpath. If no $xpath given, return the first form
     * on the page.
     *
     * @param string $xpath
     * @return \Symfony\Component\DomCrawler\Form
     * @api
     */
    public function getForm($xpath = '//form'): Form
    {
        return $this->getCrawler()->filterXPath($xpath)->form();
    }

    /**
     * Submit a form
     *
     * @param \Symfony\Component\DomCrawler\Form $form
     * @return ResponseInterface
     * @throws InfiniteRedirectionException
     * @api
     */
    public function submit(Form $form): ResponseInterface
    {
        return $this->request($form->getUri(), $form->getMethod(), $form->getPhpValues(), $form->getPhpFiles());
    }
}
