<?php
namespace Neos\RedirectHandler;

/*
 * This file is part of the Neos.RedirectHandler package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\Storage\RedirectionStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Headers;
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
     * @Flow\InjectConfiguration(path="features")
     * @var array
     */
    protected $featureSwitch;

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
            $redirection = $this->redirectionStorage->getOneBySourceUriPathAndHost($httpRequest->getRelativePath(), $httpRequest->getBaseUri()->getHost());
            if ($redirection === null) {
                return null;
            }
            if (isset($this->featureSwitch['hitCounter']) && $this->featureSwitch['hitCounter'] === true) {
                $this->redirectionStorage->incrementHitCount($redirection);
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
        if (headers_sent() === true && FLOW_SAPITYPE !== 'CLI') {
            return null;
        }
        $response = new Response();
        $statusCode = $redirection->getStatusCode();
        $response->setStatus($statusCode);
        if ($statusCode >= 300 && $statusCode <= 399) {
            $response->setHeaders(new Headers([
                'Location' => $httpRequest->getBaseUri() . $redirection->getTargetUriPath(),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT'
            ]));
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
