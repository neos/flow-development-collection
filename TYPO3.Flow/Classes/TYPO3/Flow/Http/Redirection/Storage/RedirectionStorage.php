<?php
namespace TYPO3\Flow\Http\Redirection\Storage;

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
use TYPO3\Flow\Http\Redirection\RedirectionException;
use TYPO3\Flow\Http\Redirection\Redirection as RedirectionDto;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;

/**
 * Database Storage for the Redirections
 *
 * @Flow\Scope("singleton")
 */
class RedirectionStorage implements RedirectionStorageInterface
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
     * Runtime cache to avoid creating multiple time the same redirection
     *
     * @var array
     */
    protected $runtimeCache = [];

    /**
     * Returns one redirection for the given $sourceUriPath or NULL if it doesn't exist
     *
     * @param string $sourceUriPath
     * @return RedirectionDto or NULL if no redirection exists for the given $sourceUriPath
     * @api
     */
    public function getOneBySourceUriPath($sourceUriPath)
    {
        $redirection = $this->redirectionRepository->findOneBySourceUriPath($sourceUriPath);
        if ($redirection === null) {
            return null;
        }
        return new RedirectionDto($redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode());
    }

    /**
     * Returns all registered redirection records
     *
     * @return \Generator<RedirectionDto>
     * @api
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
     * @api
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
     * @api
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
     * @api
     */
    public function addRedirection($sourceUriPath, $targetUriPath, $statusCode = 301)
    {
        $hash = md5($sourceUriPath . $targetUriPath . $statusCode);
        if (isset($this->runtimeCache[$hash])) {
            return $this->runtimeCache[$hash];
        }
        $redirection = new Redirection($sourceUriPath, $targetUriPath, $statusCode);
        $this->updateDependingRedirects($redirection);
        $this->redirectionRepository->add($redirection);
        $this->routerCachingService->flushCachesForUriPath($sourceUriPath);
        $this->runtimeCache[$hash] = $redirection;
        return new RedirectionDto($redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode());
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
