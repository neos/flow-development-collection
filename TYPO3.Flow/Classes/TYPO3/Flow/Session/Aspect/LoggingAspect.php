<?php
namespace TYPO3\Flow\Session\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect which centralizes the logging of important session actions.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class LoggingAspect {

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Logs calls of start()
	 *
	 * @Flow\After("within(TYPO3\Flow\Session\SessionInterface) && method(.*->start())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logStart(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('%s: Started session with id %s.', $this->getClassName($joinPoint), $session->getId()), LOG_INFO);
		}
	}

	/**
	 * Logs calls of resume()
	 *
	 * @Flow\After("within(TYPO3\Flow\Session\SessionInterface) && method(.*->resume())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logResume(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
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
			$this->systemLogger->log(sprintf('%s: Resumed session with id %s which was inactive for %s. (%ss)', $this->getClassName($joinPoint), $joinPoint->getProxy()->getId(), $inactivityMessage, $inactivityInSeconds), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of destroy()
	 *
	 * @Flow\Before("within(TYPO3\Flow\Session\SessionInterface) && method(.*->destroy())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logDestroy(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$reason = $joinPoint->isMethodArgument('reason') ? $joinPoint->getMethodArgument('reason') : 'no reason given';
			$this->systemLogger->log(sprintf('%s: Destroyed session with id %s: %s', $this->getClassName($joinPoint), $joinPoint->getProxy()->getId(), $reason), LOG_INFO);
		}
	}

	/**
	 * Logs calls of renewId()
	 *
	 * @Flow\Around("within(TYPO3\Flow\Session\SessionInterface) && method(.*->renewId())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logRenewId(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		$oldId = $session->getId();
		$newId = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('%s: Changed session id from %s to %s', $this->getClassName($joinPoint), $oldId, $newId), LOG_INFO);
		}
		return $newId;
	}

	/**
	 * Logs calls of collectGarbage()
	 *
	 * @Flow\AfterReturning("within(TYPO3\Flow\Session\SessionInterface) && method(.*->collectGarbage())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 */
	public function logCollectGarbage(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$sessionRemovalCount = $joinPoint->getResult();
		if ($sessionRemovalCount > 0) {
			$this->systemLogger->log(sprintf('%s: Triggered garbage collection and removed %s expired sessions.', $this->getClassName($joinPoint), $sessionRemovalCount), LOG_INFO);
		} elseif ($sessionRemovalCount === 0) {
			$this->systemLogger->log(sprintf('%s: Triggered garbage collection but no sessions needed to be removed.', $this->getClassName($joinPoint)), LOG_INFO);
		} elseif ($sessionRemovalCount === FALSE) {
			$this->systemLogger->log(sprintf('%s: Ommitting garbage collection because another process is already running. Consider lowering the GC propability if these messages appear a lot.', $this->getClassName($joinPoint)), LOG_WARNING);
		}
	}

	/**
	 * Determines the short or full class name of the session implementation
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	protected function getClassName(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$className = $joinPoint->getClassName();
		if (substr($className, 0, 18) === 'TYPO3\Flow\Session') {
			$className = substr($className, 19);
		}
		return $className;
	}

}

?>