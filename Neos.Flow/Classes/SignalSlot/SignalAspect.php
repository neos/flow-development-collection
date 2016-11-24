<?php
namespace Neos\Flow\SignalSlot;

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
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Passes the signal over to the Dispatcher
     *
     * @Flow\AfterReturning("methodAnnotatedWith(Neos\Flow\Annotations\Signal)")
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function forwardSignalToDispatcher(JoinPointInterface $joinPoint)
    {
        $signalName = lcfirst(str_replace('emit', '', $joinPoint->getMethodName()));
        $this->dispatcher->dispatch($joinPoint->getClassName(), $signalName, $joinPoint->getMethodArguments());
    }
}
