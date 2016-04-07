<?php
namespace Neos\RedirectHandler\DatabaseStorage;

/*
 * This file is part of the Neos.RedirectHandler.DatabaseStorage package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\OptimisticLockException;
use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirection;
use Neos\RedirectHandler\DatabaseStorage\Domain\Repository\RedirectionRepository;
use Neos\RedirectHandler\Exception;
use Neos\RedirectHandler\Redirection as RedirectionDto;
use Neos\RedirectHandler\Storage\RedirectionStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

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
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

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
     * @Flow\InjectConfiguration(path="statusCode", package="Neos.RedirectHandler")
     * @var array
     */
    protected $defaultStatusCode;

    /**
     * Returns one redirection for the given $sourceUriPath or NULL if it doesn't exist
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return RedirectionDto|null if no redirection exists for the given $sourceUriPath
     * @api
     */
    public function getOneBySourceUriPathAndHost($sourceUriPath, $host = null)
    {
        $redirection = $this->redirectionRepository->findOneBySourceUriPathAndHost($sourceUriPath, $host);
        if ($redirection === null) {
            return null;
        }
        return new RedirectionDto($redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode(), $redirection->getHost());
    }

    /**
     * Returns all registered redirection records
     *
     * @param string $host Full qualified hostname or host pattern
     * @return \Generator<RedirectionDto>
     * @api
     */
    public function getAll($host = null)
    {
        foreach ($this->redirectionRepository->findAll($host) as $redirection) {
            yield new RedirectionDto($redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode(), $redirection->getHost());
        }
    }

    /**
     * Return a list of all host patterns
     *
     * @return array
     * @api
     */
    public function getDistinctHosts()
    {
        return $this->redirectionRepository->findDistinctHosts();
    }

    /**
     * Removes a redirection for the given $sourceUriPath if it exists
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return void
     * @api
     */
    public function removeOneBySourceUriPathAndHost($sourceUriPath, $host = null)
    {
        $redirection = $this->redirectionRepository->findOneBySourceUriPathAndHost($sourceUriPath, $host);
        if ($redirection === null) {
            return;
        }
        $this->redirectionRepository->remove($redirection);
    }

    /**
     * Removes all registered redirection records
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     * @api
     */
    public function removeAll($host = null)
    {
        $this->redirectionRepository->removeAll($host);
    }

    /**
     * Adds a redirection to the repository and updates related redirection instances accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @param array $hosts the list of host patterns
     * @return array<Redirection> the freshly generated redirections instance
     * @api
     */
    public function addRedirection($sourceUriPath, $targetUriPath, $statusCode = null, array $hosts = [])
    {
        $statusCode = $statusCode ?: $this->defaultStatusCode['redirect'];
        $redirections = [];
        if ($hosts !== []) {
            array_map(function($host) use ($sourceUriPath, $targetUriPath, $statusCode, &$redirections) {
                $redirections[] = $this->addRedirectionByHost($sourceUriPath, $targetUriPath, $statusCode, $host);
            }, $hosts);
        } else {
            $redirections[] = $this->addRedirectionByHost($sourceUriPath, $targetUriPath, $statusCode);
        }
        return $redirections;
    }

    /**
     * Adds a redirection to the repository and updates related redirection instances accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @param string $host the host patterns for the current redirection
     * @return Redirection the freshly generated redirection instance
     * @api
     */
    protected function addRedirectionByHost($sourceUriPath, $targetUriPath, $statusCode, $host = null)
    {
        $hash = md5($host . $sourceUriPath . $targetUriPath . $statusCode);
        if (isset($this->runtimeCache[$hash])) {
            return $this->runtimeCache[$hash];
        }
        $redirection = new Redirection($sourceUriPath, $targetUriPath, $statusCode, $host);
        $this->updateDependingRedirects($redirection);
        $this->redirectionRepository->add($redirection);
        $this->routerCachingService->flushCachesForUriPath($sourceUriPath);
        $this->runtimeCache[$hash] = $redirection;
        return new RedirectionDto($redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode(), $redirection->getHost());
    }

    /**
     * Updates affected redirection instances in order to avoid redundant or circular redirects
     *
     * @param Redirection $newRedirection
     * @return void
     * @throws Exception if creating the redirect would cause conflicts
     */
    protected function updateDependingRedirects(Redirection $newRedirection)
    {
        /** @var $existingRedirectionForSourceUriPath Redirection */
        $existingRedirectionForSourceUriPath = $this->redirectionRepository->findOneBySourceUriPathAndHost($newRedirection->getSourceUriPath());
        /** @var $existingRedirectionForTargetUriPath Redirection */
        $existingRedirectionForTargetUriPath = $this->redirectionRepository->findOneBySourceUriPathAndHost($newRedirection->getTargetUriPath());

        if ($existingRedirectionForTargetUriPath !== null) {
            if ($existingRedirectionForTargetUriPath->getTargetUriPath() === $newRedirection->getSourceUriPath()) {
                $this->redirectionRepository->remove($existingRedirectionForTargetUriPath);
            } else {
                throw new Exception(sprintf('A redirect exists for the target URI path "%s", please remove it first.', $newRedirection->getTargetUriPath()), 1382091526);
            }
        }
        if ($existingRedirectionForSourceUriPath !== null) {
            throw new Exception(sprintf('A redirect exists for the source URI path "%s", please remove it first.', $newRedirection->getSourceUriPath()), 1382091456);
        }
        $obsoleteRedirectionInstances = $this->redirectionRepository->findByTargetUriPathAndHost($newRedirection->getSourceUriPath(), $newRedirection->getHost());
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

    /**
     * Increment the hit counter for the given redirection
     *
     * @param RedirectionDto $redirection
     * @return void
     * @api
     */
    public function incrementHitCount(RedirectionDto $redirection)
    {
        for ($i = 0; $i < 10; $i++) {
            try {
                $redirection = $this->redirectionRepository->findOneBySourceUriPathAndHost($redirection->getSourceUriPath(), $redirection->getHost());
                if ($redirection === null) {
                    return;
                }
                $redirection->incrementHitCounter();
                $this->redirectionRepository->update($redirection);
                $this->persistenceManager->whitelistObject($redirection);
                $this->persistenceManager->persistAll(true);
                return;
            } catch (OptimisticLockException $exception) {
                usleep($i * 10);
            }
        }
    }
}
