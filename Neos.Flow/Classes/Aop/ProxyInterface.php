<?php
namespace Neos\Flow\Aop;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract and marker interface for the AOP Proxy classes
 *
 */
interface ProxyInterface extends \Neos\Flow\ObjectManagement\Proxy\ProxyInterface
{
    /**
     * Invokes the joinpoint - calls the target methods.
     *
     * @param JoinPointInterface $joinPoint The join point
     * @return mixed Result of the target (ie. original) method
     */
    public function Flow_Aop_Proxy_invokeJoinPoint(JoinPointInterface $joinPoint);
}
