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
 * @phpstan-ignore trait.unused (probably API)
 */
trait AdvicesTrait
{
    /**
     * Used in AOP proxies to get the advice chain for a given method.
     *
     * @param string $methodName
     * @return array
     */
    private function Flow_Aop_Proxy_getAdviceChains(string $methodName): array
    {
        if (isset($this->Flow_Aop_Proxy_groupedAdviceChains[$methodName])) {
            return $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
        }

        $adviceChains = [];
        if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName])) {
            $groupedAdvices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName];
            if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AroundAdvice::class])) {
                $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName][\Neos\Flow\Aop\Advice\AroundAdvice::class] = new \Neos\Flow\Aop\Advice\AdviceChain($groupedAdvices[\Neos\Flow\Aop\Advice\AroundAdvice::class]);
                $adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
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
        $methodName = $joinPoint->getMethodName();
        if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode[$methodName])) {
            $arguments = $this->Flow_Aop_Proxy_unpackMethodArguments($methodName, $joinPoint->getMethodArguments());
            return self::$methodName(...$arguments);
        }
    }

    /**
     * Converts the method arguments of a join point into a list of arguments which can be
     * unpacked into a call of the respective method.
     *
     * A join point stores the arguments passed to a variadic parameter as one single array,
     * mapped to the name of that parameter. That array therefore needs to be unpacked again
     * before the method can be called with the arguments taken from the join point.
     *
     * @param string $methodName Name of the method the given arguments belong to
     * @param array $methodArguments The method arguments of the join point
     * @return array The arguments, ready to be unpacked into a call of the given method
     */
    private function Flow_Aop_Proxy_unpackMethodArguments(string $methodName, array $methodArguments): array
    {
        static $variadicParameterNames = [];

        $cacheKey = $this::class . '::' . $methodName;
        if (!array_key_exists($cacheKey, $variadicParameterNames)) {
            $parameters = (new \ReflectionMethod($this, $methodName))->getParameters();
            $lastParameter = end($parameters);
            $variadicParameterNames[$cacheKey] = ($lastParameter !== false && $lastParameter->isVariadic()) ? $lastParameter->getName() : null;
        }

        $variadicParameterName = $variadicParameterNames[$cacheKey];
        if ($variadicParameterName === null || !array_key_exists($variadicParameterName, $methodArguments)) {
            return array_values($methodArguments);
        }

        $variadicArguments = $methodArguments[$variadicParameterName];
        unset($methodArguments[$variadicParameterName]);
        return array_merge(array_values($methodArguments), is_array($variadicArguments) ? array_values($variadicArguments) : [$variadicArguments]);
    }
}
