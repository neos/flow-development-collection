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
use TYPO3\Flow\Persistence\QueryResultInterface;

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
     * @var RedirectionRepository
     */
    protected $redirectionRepository;

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
     * Searches for an applicable redirection record in the $redirectionRepository and sends redirect headers if one was found
     *
     * @param Request $httpRequest
     * @return void
     * @api
     */
    public function triggerRedirectIfApplicable(Request $httpRequest)
    {
        try {
            $redirection = $this->getOneBySourceUriPath($httpRequest->getRelativePath());
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

    /**
     * Returns one redirection for the given $sourceUriPath or NULL if it doesn't exist
     *
     * @param string $sourceUriPath
     * @return Redirection or NULL if no redirection exists for the given $sourceUriPath
     */
    public function getOneBySourceUriPath($sourceUriPath)
    {
        $sourceUriPath = trim($sourceUriPath, '/');
        return $this->redirectionRepository->findOneBySourceUriPath($sourceUriPath);
    }

    /**
     * Returns all registered redirection records
     *
     * @return QueryResultInterface<Redirection>
     */
    public function getAll()
    {
        return $this->redirectionRepository->findAll();
    }

    /**
     * Removes a redirection for the given $sourceUriPath if it exists
     *
     * @param string $sourceUriPath
     * @return void
     */
    public function removeOneBySourceUriPath($sourceUriPath)
    {
        $redirectionToRemove = $this->getOneBySourceUriPath($sourceUriPath);
        if ($redirectionToRemove === null) {
            return;
        }
        $this->redirectionRepository->remove($redirectionToRemove);
    }

    /**
     * Removes all registered redirection records
     *
     * @return void
     */
    public function removeAll()
    {
        $this->redirectionRepository->removeAll();
    }

    /**
     * Adds a redirection to the repository and updates related redirection instances accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @return Redirection the freshly generated redirection instance
     */
    public function addRedirection($sourceUriPath, $targetUriPath, $statusCode = 301)
    {
        $sourceUriPath = trim($sourceUriPath, '/');
        $targetUriPath = trim($targetUriPath, '/');
        $redirection = new Redirection($sourceUriPath, $targetUriPath, $statusCode);
        $this->updateDependingRedirects($redirection);
        $this->redirectionRepository->add($redirection);
        $this->routerCachingService->flushCachesForUriPath($sourceUriPath);
        return $redirection;
    }

    /**
     * Updates affected redirection instances in order to avoid redundant or circular redirects
     *
     * @param Redirection $newRedirection
     * @return void
     * @throws RedirectionException if creating the redirect would cause conflicts
     */
    protected function updateDependingRedirects(Redirection $newRedirection)
    {
        /** @var $existingRedirectionForSourceUriPath Redirection */
        $existingRedirectionForSourceUriPath = $this->redirectionRepository->findOneBySourceUriPath($newRedirection->getSourceUriPath());
        /** @var $existingRedirectionForTargetUriPath Redirection */
        $existingRedirectionForTargetUriPath = $this->redirectionRepository->findOneBySourceUriPath($newRedirection->getTargetUriPath());

        if ($existingRedirectionForTargetUriPath !== null) {
            if ($existingRedirectionForTargetUriPath->getTargetUriPath() === $newRedirection->getSourceUriPath()) {
                $this->redirectionRepository->remove($existingRedirectionForTargetUriPath);
            } else {
                throw new RedirectionException(sprintf('A redirect exists for the target URI path "%s", please remove it first.', $newRedirection->getTargetUriPath()), 1382091526);
            }
        }
        if ($existingRedirectionForSourceUriPath !== null) {
            throw new RedirectionException(sprintf('A redirect exists for the source URI path "%s", please remove it first.', $newRedirection->getSourceUriPath()), 1382091456);
        }
        $obsoleteRedirectionInstances = $this->redirectionRepository->findByTargetUriPath($newRedirection->getSourceUriPath());
        /** @var $obsoleteRedirection Redirection */
        foreach ($obsoleteRedirectionInstances as $obsoleteRedirection) {
            if ($obsoleteRedirection->getSourceUriPath() === $newRedirection->getTargetUriPath()) {
                $this->redirectionRepository->remove($obsoleteRedirection);
            } else {
                $obsoleteRedirection->setTargetUriPath($newRedirection->getTargetUriPath());
                $this->redirectionRepository->update($obsoleteRedirection);
            }
        }
    }
}
