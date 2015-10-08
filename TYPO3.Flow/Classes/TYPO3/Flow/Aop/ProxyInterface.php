<?php
namespace TYPO3\Flow\Aop;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Contract and marker interface for the AOP Proxy classes
 *
 */
interface ProxyInterface extends \TYPO3\Flow\Object\Proxy\ProxyInterface
{
    /**
     * Invokes the joinpoint - calls the target methods.
     *
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The join point
     * @return mixed Result of the target (ie. original) method
     */
    public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint);
}
