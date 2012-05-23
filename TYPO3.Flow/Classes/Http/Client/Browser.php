<?php
namespace TYPO3\FLOW3\Http\Client;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Http\Uri;
use TYPO3\FLOW3\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * An HTTP client simulating a web browser
 *
 * @api
 */
class Browser {

	/**
	 * @var \TYPO3\FLOW3\Http\Request
	 */
	protected $lastRequest;

	/**
	 * @var \TYPO3\FLOW3\Http\Response
	 */
	protected $lastResponse;

	/**
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Http\Client\RequestEngineInterface
	 */
	protected $requestEngine;

	/**
	 * Requests the given URI with the method and other parameters as specified.
	 *
	 * @param string|\TYPO3\FLOW3\Http\Uri $uri
	 * @param string $method
	 * @param array $arguments
	 * @param array $files
	 * @param array $server
	 * @param string $content
	 * @return \TYPO3\FLOW3\Http\Response The HTTP response
	 * @api
	 */
	public function request($uri, $method = 'GET', array $arguments = array(), array $files = array(), array $server = array(), $content = NULL) {
		if (is_string($uri)) {
			$uri = new Uri($uri);
		}
		if (!$uri instanceof Uri) {
			throw new \InvalidArgumentException('$uri must be a URI object or a valid string representation of a URI.', 1333443624);
		}

		$request = Request::create($uri, $method, $arguments, $this->cookies, $files, $server);
		if ($content !== NULL) {
			$request->setContent($content);
		}
		return $this->sendRequest($request);
	}

	/**
	 * Sends a prepared request and returns the respective response.
	 *
	 * @param \TYPO3\FLOW3\Http\Request $request
	 * @return \TYPO3\FLOW3\Http\Response
	 * @api
	 */
	public function sendRequest(Request $request) {
		$this->lastRequest = $request;
		$this->lastResponse = $this->requestEngine->sendRequest($request);
		return $this->lastResponse;
	}

	/**
	 * Returns the response received after the last request.
	 *
	 * @return \TYPO3\FLOW3\Http\Response The HTTP response or NULL if there wasn't a response yet
	 * @api
	 */
	public function getLastResponse() {
		return $this->lastResponse;
	}

	/**
	 * Returns the request engine used by this Browser.
	 *
	 * @return RequestEngineInterface
	 * @api
	 */
	public function getRequestEngine() {
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
	public function getCrawler() {
		$crawler = new Crawler(NULL, $this->lastRequest->getBaseUri());
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
	public function getForm($xpath = '//form') {
		return $this->getCrawler()->filterXPath($xpath)->form();
	}

	/**
	 * Submit a form
	 *
	 * @param \Symfony\Component\DomCrawler\Form $form
	 * @return \TYPO3\FLOW3\Http\Response
	 * @api
	 */
	public function submit(Form $form) {
		return $this->request($form->getUri(), $form->getMethod(), $form->getPhpValues(), $form->getPhpFiles());
	}
}

?>