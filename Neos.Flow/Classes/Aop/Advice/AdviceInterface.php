<?php
namespace Neos\Flow\Aop\Advice;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Aop\JoinPointInterface;

/**
 * This is the interface for a generic AOP advice. It is never implemented directly.
 * In Flow all advices are implemented as interceptors.
 *
 * @see \Neos\Flow\Aop\InterceptorInterface
 */
interface AdviceInterface
{
    /**
     * Invokes the advice method
     *
     * @param  JoinPointInterface $joinPoint The current join point which is passed to the advice method
     * @return mixed Optionally the result of the advice method
     */
    public function invoke(JoinPointInterface $joinPoint);

    /**
     * Returns the aspect's object name which has been passed to the constructor
     *
     * @return string The object name of the aspect
     */
    public function getAspectObjectName();

    /**
     * Returns the advice's method name which has been passed to the constructor
     *
     * @return string The name of the advice method
     */
    public function getAdviceMethodName();
}
