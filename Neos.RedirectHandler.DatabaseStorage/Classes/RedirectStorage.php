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
use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect;
use Neos\RedirectHandler\DatabaseStorage\Domain\Repository\RedirectRepository;
use Neos\RedirectHandler\Exception;
use Neos\RedirectHandler\Redirect as RedirectDto;
use Neos\RedirectHandler\RedirectInterface;
use Neos\RedirectHandler\Storage\RedirectStorageInterface;
use Neos\RedirectHandler\Traits\RedirectSignalTrait;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

/**
 * Database Storage for the Redirects
 *
 * @Flow\Scope("singleton")
 */
class RedirectStorage implements RedirectStorageInterface
{
    use RedirectSignalTrait;

    /**
     * @Flow\Inject
     * @var RedirectRepository
     */
    protected $redirectRepository;

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
     * Runtime cache to avoid creating multiple time the same redirect
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
     * Returns one redirect for the given $sourceUriPath or NULL if it doesn't exist
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return RedirectDto|null if no redirect exists for the given $sourceUriPath
     * @api
     */
    public function getOneBySourceUriPathAndHost($sourceUriPath, $host = null)
    {
        $redirect = $this->redirectRepository->findOneBySourceUriPathAndHost($sourceUriPath, $host);
        if ($redirect === null) {
            return null;
        }
        return RedirectDto::create($redirect);
    }

    /**
     * Returns all registered redirects records
     *
     * @param string $host Full qualified hostname or host pattern
     * @return \Generator<RedirectDto>
     * @api
     */
    public function getAll($host = null)
    {
        foreach ($this->redirectRepository->findAll($host) as $redirect) {
            yield RedirectDto::create($redirect);
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
        return $this->redirectRepository->findDistinctHosts();
    }

    /**
     * Removes a redirect for the given $sourceUriPath if it exists
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return void
     * @api
     */
    public function removeOneBySourceUriPathAndHost($sourceUriPath, $host = null)
    {
        $redirect = $this->redirectRepository->findOneBySourceUriPathAndHost($sourceUriPath, $host);
        if ($redirect === null) {
            return;
        }
        $this->redirectRepository->remove($redirect);
    }

    /**
     * Removes all registered redirects
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     * @api
     */
    public function removeAll($host = null)
    {
        $this->redirectRepository->removeAll($host);
    }

    /**
     * Adds a redirect to the repository and updates related redirect instances accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @param array $hosts the list of host patterns
     * @return array<Redirect> the freshly generated redirects instance
     * @api
     */
    public function addRedirection($sourceUriPath, $targetUriPath, $statusCode = null, array $hosts = [])
    {
        $statusCode = $statusCode ?: (integer)$this->defaultStatusCode['redirect'];
        $redirects = [];
        if ($hosts !== []) {
            array_map(function($host) use ($sourceUriPath, $targetUriPath, $statusCode, &$redirects) {
                $redirects[] = $this->addRedirectionByHost($sourceUriPath, $targetUriPath, $statusCode, $host);
            }, $hosts);
        } else {
            $redirects[] = $this->addRedirectionByHost($sourceUriPath, $targetUriPath, $statusCode);
        }
        $this->emitRedirectionCreated($redirects);
        return $redirects;
    }

    /**
     * Adds a redirect to the repository and updates related redirects accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @param string $host the host patterns for the current redirect
     * @return Redirect the freshly generated redirect DTO instance
     * @api
     */
    protected function addRedirectionByHost($sourceUriPath, $targetUriPath, $statusCode, $host = null)
    {
        $hash = md5($host . $sourceUriPath . $targetUriPath . $statusCode);
        if (isset($this->runtimeCache[$hash])) {
            return $this->runtimeCache[$hash];
        }
        $redirect = new Redirect($sourceUriPath, $targetUriPath, $statusCode, $host);
        $this->updateDependingRedirects($redirect);
        $this->redirectRepository->add($redirect);
        $this->routerCachingService->flushCachesForUriPath($sourceUriPath);
        $this->runtimeCache[$hash] = $redirect;
        return RedirectDto::create($redirect);
    }

    /**
     * Updates affected redirects in order to avoid redundant or circular redirections
     *
     * @param RedirectInterface $newRedirect
     * @return void
     * @throws Exception if creating the redirect would cause conflicts
     */
    protected function updateDependingRedirects(RedirectInterface $newRedirect)
    {
        /** @var $existingRedirectForSourceUriPath Redirect */
        $existingRedirectForSourceUriPath = $this->redirectRepository->findOneBySourceUriPathAndHost($newRedirect->getSourceUriPath());
        /** @var $existingRedirectForTargetUriPath Redirect */
        $existingRedirectForTargetUriPath = $this->redirectRepository->findOneBySourceUriPathAndHost($newRedirect->getTargetUriPath());

        if ($existingRedirectForTargetUriPath !== null) {
            if ($existingRedirectForTargetUriPath->getTargetUriPath() === $newRedirect->getSourceUriPath()) {
                $this->redirectRepository->remove($existingRedirectForTargetUriPath);
            } else {
                throw new Exception(sprintf('A redirect exists for the target URI path "%s", please remove it first.', $newRedirect->getTargetUriPath()), 1382091526);
            }
        }
        if ($existingRedirectForSourceUriPath !== null) {
            throw new Exception(sprintf('A redirect exists for the source URI path "%s", please remove it first.', $newRedirect->getSourceUriPath()), 1382091456);
        }
        $obsoleteRedirectInstances = $this->redirectRepository->findByTargetUriPathAndHost($newRedirect->getSourceUriPath(), $newRedirect->getHost());
        /** @var $obsoleteRedirect Redirect */
        foreach ($obsoleteRedirectInstances as $obsoleteRedirect) {
            if ($obsoleteRedirect->getSourceUriPath() === $newRedirect->getTargetUriPath()) {
                $this->redirectRepository->remove($obsoleteRedirect);
            } else {
                $obsoleteRedirect->setTargetUriPath($newRedirect->getTargetUriPath());
                $this->redirectRepository->update($obsoleteRedirect);
            }
        }
    }

    /**
     * Increment the hit counter for the given redirect
     *
     * @param RedirectInterface $redirect
     * @return void
     * @api
     */
    public function incrementHitCount(RedirectInterface $redirect)
    {
        for ($i = 0; $i < 10; $i++) {
            try {
                $redirect = $this->redirectRepository->findOneBySourceUriPathAndHost($redirect->getSourceUriPath(), $redirect->getHost());
                if ($redirect === null) {
                    return;
                }
                $redirect->incrementHitCounter();
                $this->redirectRepository->update($redirect);
                $this->persistenceManager->whitelistObject($redirect);
                $this->persistenceManager->persistAll(true);
                return;
            } catch (OptimisticLockException $exception) {
                usleep($i * 10);
            }
        }
    }
}
