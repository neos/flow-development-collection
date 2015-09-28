<?php
namespace TYPO3\Flow\Aop\Advice;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * This is the interface for a generic AOP advice. It is never implemented directly.
 * In Flow all advices are implemented as interceptors.
 *
 * @see \TYPO3\Flow\Aop\InterceptorInterface
 */
interface AdviceInterface
{
    /**
     * Invokes the advice method
     *
     * @param  \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point which is passed to the advice method
     * @return mixed Optionally the result of the advice method
     */
    public function invoke(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint);

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
