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

/**
 * An aspect which centralizes the logging of important session actions.
 *
 * @scope singleton
 * @aspect
 */
class LoggingAspect {

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 * @inject
	 */
	protected $systemLogger;

	/**
	 * Logs calls of start()
	 *
	 * @after within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->start())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logStart(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('Started session with id %s', $session->getId()), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of resume()
	 *
	 * @after within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->resume())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logResume(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('Resumed session with id %s', $joinPoint->getProxy()->getId()), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of destroy()
	 *
	 * @before within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->destroy())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logDestroy(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('Destroyed session with id %s', $joinPoint->getProxy()->getId()), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of renewId()
	 *
	 * @around within(TYPO3\FLOW3\Session\SessionInterface) && method(.*->renewId())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logRenewId(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		$oldId = $session->getId();
		$newId = $joinPoint->getAdviceChain()->proceed($joinPoint);

		$this->systemLogger->log(sprintf('Changed session id from %s to %s', $oldId, $newId), LOG_DEBUG);

		return $newId;
	}
}

?>