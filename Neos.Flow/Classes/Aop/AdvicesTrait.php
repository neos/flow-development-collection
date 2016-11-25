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
 * Contains boilerplate code for AOP execution and is added to AOP proxy classes.
 *
 */
trait AdvicesTrait
{
    /**
     * Used in AOP proxies to get the advice chain for a given method.
     *
     * @param string $methodName
     * @return array
     */
    private function Flow_Aop_Proxy_getAdviceChains($methodName)
    {
        $adviceChains = [];
        if (isset($this->Flow_Aop_Proxy_groupedAdviceChains[$methodName])) {
            $adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
        } else {
            if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName])) {
                $groupedAdvices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName];
                if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AroundAdvice::class])) {
                    $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName][\Neos\Flow\Aop\Advice\AroundAdvice::class] = new \Neos\Flow\Aop\Advice\AdviceChain($groupedAdvices[\Neos\Flow\Aop\Advice\AroundAdvice::class]);
                    $adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
                }
            }
        }

        return $adviceChains;
    }

    /**
     * Invokes a given join point
     *
     * @param \Neos\Flow\Aop\JoinPointInterface $joinPoint
     * @return mixed
     */
    public function Flow_Aop_Proxy_invokeJoinPoint(\Neos\Flow\Aop\JoinPointInterface $joinPoint)
    {
        if (__CLASS__ !== $joinPoint->getClassName()) {
            return parent::Flow_Aop_Proxy_invokeJoinPoint($joinPoint);
        }
        if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode[$joinPoint->getMethodName()])) {
            return call_user_func_array(['self', $joinPoint->getMethodName()], $joinPoint->getMethodArguments());
        }
    }
}
