<?php
namespace TYPO3\FLOW3\Session\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect which centralizes the logging of important session actions.
 *
 * @FLOW3\Aspect
 * @FLOW3\Scope("singleton")
 */
class LoggingAspect {

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 * @FLOW3\Inject
	 */
	protected $systemLogger;

	/**
	 * Logs calls of start()
	 *
	 * @FLOW3\After("within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->start())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logStart(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('Started session with id %s', $session->getId()), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of resume()
	 *
	 * @FLOW3\After("within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->resume())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logResume(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('Resumed session with id %s which was inactive for %s seconds.', $joinPoint->getProxy()->getId(), $joinPoint->getResult()), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of destroy()
	 *
	 * @FLOW3\Before("within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->destroy())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logDestroy(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$reason = $joinPoint->isMethodArgument('reason') ? $joinPoint->getMethodArgument('reason') : 'no reason given';
			$this->systemLogger->log(sprintf('Destroyed session with id %s: %s', $joinPoint->getProxy()->getId(), $reason), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of renewId()
	 *
	 * @FLOW3\Around("within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->renewId())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logRenewId(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		$newId = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($session->isStarted()) {
			$oldId = $session->getId();
			$this->systemLogger->log(sprintf('Changed session id from %s to %s', $oldId, $newId), LOG_DEBUG);
		}
		return $newId;
	}
}

?>