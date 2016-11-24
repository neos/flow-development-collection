<?php
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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Headers;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Http\Request;
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
     * @var Request
     */
    protected $lastRequest;

    /**
     * @var Response
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
    public function setRequestEngine(RequestEngineInterface $requestEngine)
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
    public function addAutomaticRequestHeader($name, $values)
    {
        $this->automaticRequestHeaders->set($name, $values, true);
    }

    /**
     * Allows to remove headers that were added with addAutomaticRequestHeader.
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @return void
     */
    public function removeAutomaticRequestHeader($name)
    {
        $this->automaticRequestHeaders->remove($name);
    }

    /**
     * Requests the given URI with the method and other parameters as specified.
     * If a Location header was given and the status code is of response type 3xx
     * (see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html, 14.30 Location)
     *
     * @param string|Uri $uri
     * @param string $method Request method, for example "GET"
     * @param array $arguments Arguments to send in the request body
     * @param array $files
     * @param array $server
     * @param string $content
     * @return Response The HTTP response
     * @throws \InvalidArgumentException
     * @throws InfiniteRedirectionException
     * @api
     */
    public function request($uri, $method = 'GET', array $arguments = [], array $files = [], array $server = [], $content = null)
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        if (!$uri instanceof Uri) {
            throw new \InvalidArgumentException('$uri must be a URI object or a valid string representation of a URI.', 1333443624);
        }

        $request = Request::create($uri, $method, $arguments, $files, $server);

        if ($content !== null) {
            $request->setContent($content);
        }
        $response = $this->sendRequest($request);

        $location = $response->getHeader('Location');
        if ($this->followRedirects && $location !== null && $response->getStatusCode() >= 300 && $response->getStatusCode() <= 399) {
            if (in_array($location, $this->redirectionStack) || count($this->redirectionStack) >= $this->maximumRedirections) {
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
    public function setFollowRedirects($flag)
    {
        $this->followRedirects = (boolean)$flag;
    }

    /**
     * Sends a prepared request and returns the respective response.
     *
     * @param Request $request
     * @return Response
     * @api
     */
    public function sendRequest(Request $request)
    {
        foreach ($this->automaticRequestHeaders->getAll() as $name => $values) {
            $request->setHeader($name, $values);
        }

        $this->lastRequest = $request;
        $this->lastResponse = $this->requestEngine->sendRequest($request);
        return $this->lastResponse;
    }

    /**
     * Returns the response received after the last request.
     *
     * @return Response The HTTP response or NULL if there wasn't a response yet
     * @api
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Returns the last request executed.
     *
     * @return Request The HTTP request or NULL if there wasn't a request yet
     * @api
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Returns the request engine used by this Browser.
     *
     * @return RequestEngineInterface
     * @api
     */
    public function getRequestEngine()
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
    public function getCrawler()
    {
        $crawler = new Crawler(null, $this->lastRequest->getBaseUri());
        $crawler->addContent($this->lastResponse->getContent(), $this->lastResponse->getHeader('Content-Type'));

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
    public function getForm($xpath = '//form')
    {
        return $this->getCrawler()->filterXPath($xpath)->form();
    }

    /**
     * Submit a form
     *
     * @param \Symfony\Component\DomCrawler\Form $form
     * @return Response
     * @api
     */
    public function submit(Form $form)
    {
        return $this->request($form->getUri(), $form->getMethod(), $form->getPhpValues(), $form->getPhpFiles());
    }
}
