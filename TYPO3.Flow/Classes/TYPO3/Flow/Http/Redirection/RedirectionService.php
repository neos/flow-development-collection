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
use TYPO3\Flow\Http\Redirection\Storage\RedirectionStorageInterface;
use TYPO3\Flow\Http\Request as Request;
use TYPO3\Flow\Http\Response;
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
     * Searches for an applicable redirection record and create HTTP response
     *
     * @param Request $httpRequest
     * @return Response|null
     * @api
     */
    public function buildResponseIfApplicable(Request $httpRequest)
    {
        try {
            $redirection = $this->redirectionStorage->getOneBySourceUriPath($httpRequest->getRelativePath());
            if ($redirection === null) {
                return null;
            }
            return $this->buildResponse($httpRequest, $redirection);
        } catch (\Exception $exception) {
            // skip triggering the redirect if there was an error accessing the database (wrong credentials, ...)
            return null;
        }
    }

    /**
     * @param Request $httpRequest
     * @param Redirection $redirection
     * @return Response|null
     */
    protected function buildResponse(Request $httpRequest, Redirection $redirection)
    {
        if (headers_sent() === true) {
            return null;
        }
        $response = new Response();
        $statusCode = $redirection->getStatusCode();
        $response->setStatus($statusCode);
        if ($statusCode >= 300 && $statusCode <= 399) {
            $response->setHeader('Location', $httpRequest->getBaseUri() . $redirection->getTargetUriPath());
        }
        return $response;
    }

    /**
     * Signals that a redirection has been created.
     *
     * @param Redirection $redirection
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitRedirectionCreated(Redirection $redirection)
    {
    }
}
