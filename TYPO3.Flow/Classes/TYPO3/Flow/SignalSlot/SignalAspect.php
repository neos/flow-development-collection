<?php
namespace TYPO3\Flow\SignalSlot;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Aspect which connects signal methods with the Signal Dispatcher
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class SignalAspect
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\SignalSlot\Dispatcher
     */
    protected $dispatcher;

    /**
     * Passes the signal over to the Dispatcher
     *
     * @Flow\AfterReturning("methodAnnotatedWith(TYPO3\Flow\Annotations\Signal)")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function forwardSignalToDispatcher(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $signalName = lcfirst(str_replace('emit', '', $joinPoint->getMethodName()));
        $this->dispatcher->dispatch($joinPoint->getClassName(), $signalName, $joinPoint->getMethodArguments());
    }
}
