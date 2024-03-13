<?php
declare(strict_types=1);
namespace Neos\Flow\Session\Aspect;

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
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * An aspect which centralizes the logging of important session actions.
 */
#[Flow\Aspect]
#[Flow\Scope("singleton")]
class LoggingAspect
{
    #[Flow\Inject]
    protected ?LoggerInterface $logger = null;

    /**
     * @throws SessionNotStartedException
     */
    #[Flow\After("within(Neos\Flow\Session\SessionInterface) && method(.*->start())")]
    public function logStart(JoinPointInterface $joinPoint): void
    {
        /** @var SessionInterface $session */
        $session = $joinPoint->getProxy();
        if ($session->isStarted()) {
            $this->logger?->info(sprintf('%s: Started session with id %s.', $this->getSessionImplementationClassName($joinPoint), $session->getId()));
        }
    }

    #[Flow\After("within(Neos\Flow\Session\SessionInterface) && method(.*->resume())")]
    public function logResume(JoinPointInterface $joinPoint): void
    {
        $session = $joinPoint->getProxy();
        if ($session instanceof SessionInterface && $session->isStarted()) {
            $inactivityInSeconds = $joinPoint->getResult();
            if ($inactivityInSeconds === 1) {
                $inactivityMessage = '1 second';
            } elseif ($inactivityInSeconds < 120) {
                $inactivityMessage = sprintf('%s seconds', $inactivityInSeconds);
            } elseif ($inactivityInSeconds < 3600) {
                $inactivityMessage = sprintf('%s minutes', (int)($inactivityInSeconds / 60));
            } elseif ($inactivityInSeconds < 7200) {
                $inactivityMessage = 'more than an hour';
            } else {
                $inactivityMessage = sprintf('more than %s hours', (int)($inactivityInSeconds / 3600));
            }
            $this->logger?->debug(sprintf('%s: Resumed session with id %s which was inactive for %s. (%ss)', $this->getSessionImplementationClassName($joinPoint), $session->getId(), $inactivityMessage, $inactivityInSeconds));
        }
    }

    /**
     * @throws SessionNotStartedException
     */
    #[Flow\Before("within(Neos\Flow\Session\SessionInterface) && method(.*->destroy())")]
    public function logDestroy(JoinPointInterface $joinPoint): void
    {
        $session = $joinPoint->getProxy();
        if ($session instanceof SessionInterface && $session->isStarted()) {
            $reason = $joinPoint->isMethodArgument('reason') ? $joinPoint->getMethodArgument('reason') : 'no reason given';
            $this->logger?->debug(sprintf('%s: Destroyed session with id %s: %s', $this->getSessionImplementationClassName($joinPoint), $session->getId(), $reason));
        }
    }

    /**
     * @throws SessionNotStartedException
     */
    #[Flow\Around("within(Neos\Flow\Session\SessionInterface) && method(.*->renewId())")]
    public function logRenewId(JoinPointInterface $joinPoint): mixed
    {
        $session = $joinPoint->getProxy();
        $newId = $joinPoint->getAdviceChain()->proceed($joinPoint);
        if (($session instanceof SessionInterface) && $session->isStarted()) {
            $this->logger?->info(sprintf('%s: Changed session id from %s to %s', $this->getSessionImplementationClassName($joinPoint), $session->getId(), $newId));
        }
        return $newId;
    }

    #[Flow\AfterReturning("within(Neos\Flow\Session\SessionInterface) && method(.*->collectGarbage())")]
    public function logCollectGarbage(JoinPointInterface $joinPoint): void
    {
        $sessionRemovalCount = $joinPoint->getResult();
        if ($sessionRemovalCount > 0) {
            $this->logger?->info(sprintf('%s: Triggered garbage collection and removed %s expired sessions.', $this->getSessionImplementationClassName($joinPoint), $sessionRemovalCount));
        } elseif ($sessionRemovalCount === 0) {
            $this->logger?->info(sprintf('%s: Triggered garbage collection but no sessions needed to be removed.', $this->getSessionImplementationClassName($joinPoint)));
        } elseif ($sessionRemovalCount === false) {
            $this->logger?->warning(sprintf('%s: Ommitting garbage collection because another process is already running. Consider lowering the GC probability if these messages appear a lot.', $this->getSessionImplementationClassName($joinPoint)));
        }
    }

    protected function getSessionImplementationClassName(JoinPointInterface $joinPoint): string
    {
        $className = $joinPoint->getClassName();
        $sessionNamespace = substr(SessionInterface::class, 0, -strrpos(SessionInterface::class, '\\') + 1);
        if (str_starts_with($className, $sessionNamespace)) {
            $className = substr($className, strlen($sessionNamespace));
        }
        return $className;
    }
}
