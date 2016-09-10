<?php
namespace TYPO3\Flow\Session\Aspect;

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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Session\SessionInterface;

/**
 * Adds the aspect of lazy loading to objects with scope session.
 *
 * @Flow\Aspect
 * @Flow\Introduce("filter(TYPO3\Flow\Session\Aspect\SessionObjectMethodsPointcutFilter)", interfaceName = "TYPO3\Flow\Session\Aspect\LazyLoadingProxyInterface")
 * @Flow\Scope("singleton")
 */
class LazyLoadingAspect
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var array
     */
    protected $sessionOriginalInstances = [];

    /**
     * Registers an object of scope session.
     *
     * @param string $objectName
     * @param object $object
     * @return void
     * @see \TYPO3\Flow\Object\ObjectManager
     */
    public function registerSessionInstance($objectName, $object)
    {
        $this->sessionOriginalInstances[$objectName] = $object;
    }

    /**
     * Before advice for all methods annotated with "@Flow\Session(autoStart=true)".
     * Those methods will trigger a session initialization if a session does not exist
     * yet.
     *
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     * @fixme The pointcut expression below does not consider the options of the session annotation ‚Äì¬†needs adjustments in the AOP framework
     * @Flow\Before("methodAnnotatedWith(TYPO3\Flow\Annotations\Session)")
     */
    public function initializeSession(JoinPointInterface $joinPoint)
    {
        if ($this->session->isStarted() === true) {
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
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     * @Flow\Around("filter(TYPO3\Flow\Session\Aspect\SessionObjectMethodsPointcutFilter)")
     */
    public function callMethodOnOriginalSessionObject(JoinPointInterface $joinPoint)
    {
        $objectName = $this->objectManager->getObjectNameByClassName(get_class($joinPoint->getProxy()));
        $methodName = $joinPoint->getMethodName();
        $proxy = $joinPoint->getProxy();

        if (!isset($this->sessionOriginalInstances[$objectName])) {
            $this->sessionOriginalInstances[$objectName] = $this->objectManager->get($objectName);
        }

        if ($this->sessionOriginalInstances[$objectName] === $proxy) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        } else {
            return call_user_func_array([$this->sessionOriginalInstances[$objectName], $methodName], $joinPoint->getMethodArguments());
        }
    }
}
