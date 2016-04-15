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

use Neos\RedirectHandler\Storage\RedirectStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Headers;
use TYPO3\Flow\Http\Request as Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;

/**
 * Central authority for HTTP redirects.
 *
 * This service is used to redirect to any configured target URI *before* the Routing Framework kicks in and it
 * should be used to create new redirect instances.
 *
 * @Flow\Scope("singleton")
 */
class RedirectionService
{
    /**
     * @Flow\Inject
     * @var RedirectStorageInterface
     */
    protected $redirectStorage;

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
     * Searches for a matching redirect for the given HTTP response
     *
     * @param Request $httpRequest
     * @return Response|null
     * @api
     */
    public function buildResponseIfApplicable(Request $httpRequest)
    {
        try {
            $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($httpRequest->getRelativePath(), $httpRequest->getBaseUri()->getHost());
            if ($redirect === null) {
                return null;
            }
            if (isset($this->featureSwitch['hitCounter']) && $this->featureSwitch['hitCounter'] === true) {
                $this->redirectStorage->incrementHitCount($redirect);
            }
            return $this->buildResponse($httpRequest, $redirect);
        } catch (\Exception $exception) {
            // skip triggering the redirect if there was an error accessing the database (wrong credentials, ...)
            return null;
        }
    }

    /**
     * @param Request $httpRequest
     * @param RedirectInterface $redirect
     * @return Response|null
     */
    protected function buildResponse(Request $httpRequest, RedirectInterface $redirect)
    {
        if (headers_sent() === true && FLOW_SAPITYPE !== 'CLI') {
            return null;
        }
        $response = new Response();
        $statusCode = $redirect->getStatusCode();
        $response->setStatus($statusCode);
        if ($statusCode >= 300 && $statusCode <= 399) {
            $response->setHeaders(new Headers([
                'Location' => $httpRequest->getBaseUri() . $redirect->getTargetUriPath(),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT'
            ]));
        }
        return $response;
    }

    /**
     * Signals that a redirection has been created.
     *
     * @param RedirectInterface $redirect
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitRedirectionCreated(RedirectInterface $redirect)
    {
    }
}
