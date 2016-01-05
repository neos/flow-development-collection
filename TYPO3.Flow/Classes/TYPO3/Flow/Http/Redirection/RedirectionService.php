<?php
namespace TYPO3\Flow\Http\Redirection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request as Request;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;

/**
 * Central authority for HTTP redirects.
 * This service is used to redirect to any configured target URI *before* the Routing Framework kicks in and it
 * should be used to create new redirection instances.
 *
 * @Flow\Scope("singleton")
 */
class RedirectionService
{
    /**
     * @Flow\Inject
     * @var RedirectionStorageInterface
     */
    protected $redirectionStorage;

    /**
     * @Flow\Inject
     * @var RouterCachingService
     */
    protected $routerCachingService;

    /**
     * Make exit() a closure so it can be manipulated during tests
     *
     * @var \Closure
     */
    protected $exit;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->exit = function () { exit; };
    }

    /**
     * Searches for an applicable redirection record and sends redirect headers if one was found
     *
     * @param Request $httpRequest
     * @return void
     * @api
     */
    public function triggerRedirectIfApplicable(Request $httpRequest)
    {
        try {
            $redirection = $this->redirectionStorage->getOneBySourceUriPath($httpRequest->getRelativePath());
        } catch (\Exception $exception) {
            // skip triggering the redirect if there was an error accessing the database (wrong credentials, ...)
            return;
        }
        if ($redirection === null) {
            return;
        }
        $this->sendRedirectHeaders($httpRequest, $redirection);
        $this->exit->__invoke();
    }

    /**
     * @param Request $httpRequest
     * @param Redirection $redirection
     * @return void
     */
    protected function sendRedirectHeaders(Request $httpRequest, Redirection $redirection)
    {
        if (headers_sent() === true) {
            return;
        }
        if ($redirection->getStatusCode() >= 300 && $redirection->getStatusCode() <= 399) {
            header('Location: ' . $httpRequest->getBaseUri() . $redirection->getTargetUriPath());
        }
        header($redirection->getStatusLine());
    }
}
