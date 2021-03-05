<?php
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
use Neos\Flow\Session\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * An aspect which centralizes the logging of important session actions.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class LoggingAspect
{
    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Logs calls of start()
     *
     * @Flow\After("within(Neos\Flow\Session\SessionInterface) && method(.*->start())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return mixed The result of the target method if it has not been intercepted
     */
    public function logStart(JoinPointInterface $joinPoint)
    {
        $session = $joinPoint->getProxy();
        if ($session->isStarted()) {
            $this->logger->info(sprintf('%s: Started session with id %s.', $this->getClassName($joinPoint), $session->getId()), [
                'packageKey' => 'Neos.Flow',
                'className' => $joinPoint->getClassName(),
                'methodName' => $joinPoint->getMethodName()
            ]);
        }
    }

    /**
     * Logs calls of resume()
     *
     * @Flow\After("within(Neos\Flow\Session\SessionInterface) && method(.*->resume())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return mixed The result of the target method if it has not been intercepted
     */
    public function logResume(JoinPointInterface $joinPoint)
    {
        $session = $joinPoint->getProxy();
        if ($session->isStarted()) {
            $inactivityInSeconds = $joinPoint->getResult();
            if ($inactivityInSeconds === 1) {
                $inactivityMessage = '1 second';
            } elseif ($inactivityInSeconds < 120) {
                $inactivityMessage = sprintf('%s seconds', $inactivityInSeconds);
            } elseif ($inactivityInSeconds < 3600) {
                $inactivityMessage = sprintf('%s minutes', intval($inactivityInSeconds / 60));
            } elseif ($inactivityInSeconds < 7200) {
                $inactivityMessage = 'more than an hour';
            } else {
                $inactivityMessage = sprintf('more than %s hours', intval($inactivityInSeconds / 3600));
            }
            $this->logger->debug(sprintf('%s: Resumed session with id %s which was inactive for %s. (%ss)', $this->getClassName($joinPoint), $joinPoint->getProxy()->getId(), $inactivityMessage, $inactivityInSeconds));
        }
    }

    /**
     * Logs calls of destroy()
     *
     * @Flow\Before("within(Neos\Flow\Session\SessionInterface) && method(.*->destroy())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return mixed The result of the target method if it has not been intercepted
     */
    public function logDestroy(JoinPointInterface $joinPoint)
    {
        $session = $joinPoint->getProxy();
        if ($session->isStarted()) {
            $reason = $joinPoint->isMethodArgument('reason') ? $joinPoint->getMethodArgument('reason') : 'no reason given';
            $this->logger->debug(sprintf('%s: Destroyed session with id %s: %s', $this->getClassName($joinPoint), $joinPoint->getProxy()->getId(), $reason));
        }
    }

    /**
     * Logs calls of renewId()
     *
     * @Flow\Around("within(Neos\Flow\Session\SessionInterface) && method(.*->renewId())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return mixed The result of the target method if it has not been intercepted
     */
    public function logRenewId(JoinPointInterface $joinPoint)
    {
        $session = $joinPoint->getProxy();
        $oldId = $session->getId();
        $newId = $joinPoint->getAdviceChain()->proceed($joinPoint);
        if ($session->isStarted()) {
            $this->logger->info(sprintf('%s: Changed session id from %s to %s', $this->getClassName($joinPoint), $oldId, $newId), ['FLOW_LOG_ENVIRONMENT' => [
                'packageKey' => 'Neos.Flow',
                'className' => $joinPoint->getClassName(),
                'methodName' => $joinPoint->getMethodName()
            ]]);
        }
        return $newId;
    }

    /**
     * Logs calls of collectGarbage()
     *
     * @Flow\AfterReturning("within(Neos\Flow\Session\SessionInterface) && method(.*->collectGarbage())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return void
     */
    public function logCollectGarbage(JoinPointInterface $joinPoint)
    {
        $logEnvironment = [
            'FLOW_LOG_ENVIRONMENT' => [
                'packageKey' => 'Neos.Flow',
                'className' => $joinPoint->getClassName(),
                'methodName' => $joinPoint->getMethodName()
            ]
        ];

        $sessionRemovalCount = $joinPoint->getResult();
        if ($sessionRemovalCount > 0) {
            $this->logger->info(sprintf('%s: Triggered garbage collection and removed %s expired sessions.', $this->getClassName($joinPoint), $sessionRemovalCount), $logEnvironment);
        } elseif ($sessionRemovalCount === 0) {
            $this->logger->info(sprintf('%s: Triggered garbage collection but no sessions needed to be removed.', $this->getClassName($joinPoint)), $logEnvironment);
        } elseif ($sessionRemovalCount === false) {
            $this->logger->warning(sprintf('%s: Ommitting garbage collection because another process is already running. Consider lowering the GC propability if these messages appear a lot.', $this->getClassName($joinPoint)), $logEnvironment);
        }
    }

    /**
     * Determines the short or full class name of the session implementation
     *
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    protected function getClassName(JoinPointInterface $joinPoint)
    {
        $className = $joinPoint->getClassName();
        $sessionNamespace = substr(SessionInterface::class, 0, -strrpos(SessionInterface::class, '\\') + 1);
        if (strpos($className, $sessionNamespace) === 0) {
            $className = substr($className, strlen($sessionNamespace));
        }
        return $className;
    }
}
