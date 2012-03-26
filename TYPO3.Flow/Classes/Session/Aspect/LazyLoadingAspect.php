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
 * Adds the aspect of lazy loading to objects with scope session.
 *
 * @FLOW3\Aspect
 * @FLOW3\Introduce("filter(TYPO3\FLOW3\Session\Aspect\SessionObjectMethodsPointcutFilter)", interfaceName = "TYPO3\FLOW3\Session\Aspect\LazyLoadingProxyInterface")
 * @FLOW3\Scope("singleton")
 */
class LazyLoadingAspect {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $sessionOriginalInstances = array();

	/**
	 * Registers an object of scope session.
	 *
	 * @param string $objectName
	 * @param object $object
	 * @return void
	 * @see \TYPO3\FLOW3\Object\ObjectManager
	 */
	public function registerSessionInstance($objectName, $object) {
		$this->sessionOriginalInstances[$objectName] = $object;
	}

	/**
	 * Before advice for all methods annotated with "@FLOW3\Session(autoStart=true)".
	 * Those methods will trigger a session initialization if a session does not exist
	 * yet.
	 *
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @fixme The pointcut expression below does not consider the options of the session annotation ‚Äì¬†needs adjustments in the AOP framework
	 * @FLOW3\Before("methodAnnotatedWith(TYPO3\FLOW3\Annotations\Session)")
	 */
	public function initializeSession(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		if ($this->session->isStarted() === TRUE) {
			return;
		}

		$objectName = $this->objectManager->getObjectNameByClassName(get_class($joinPoint->getProxy()));
		$methodName = $joinPoint->getMethodName();

		$this->systemLogger->log(sprintf('Session initialization triggered by %s->%s.', $objectName, $methodName), LOG_DEBUG);
		$this->session->start();
	}

	/**
	 * Around advice, wrapping every method of a scope session object. It redirects
	 * all method calls to the session object once there is one.
	 *
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @FLOW3\Around("filter(TYPO3\FLOW3\Session\Aspect\SessionObjectMethodsPointcutFilter)")
	 */
	public function callMethodOnOriginalSessionObject(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$objectName = $this->objectManager->getObjectNameByClassName(get_class($joinPoint->getProxy()));
		$methodName = $joinPoint->getMethodName();
		$proxy = $joinPoint->getProxy();

		if (!isset($this->sessionOriginalInstances[$objectName])) {
			$this->sessionOriginalInstances[$objectName] = $this->objectManager->get($objectName);
		}

		if ($this->sessionOriginalInstances[$objectName] === $proxy) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		} else {
			return call_user_func_array(array($this->sessionOriginalInstances[$objectName], $methodName), $joinPoint->getMethodArguments());
		}
	}

}
?>