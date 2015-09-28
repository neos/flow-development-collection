<?php
namespace TYPO3\Flow\Aop\Advice;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Implementation of the Around Advice.
 *
 */
class AroundAdvice extends \TYPO3\Flow\Aop\Advice\AbstractAdvice implements \TYPO3\Flow\Aop\Advice\AdviceInterface
{
    /**
     * Invokes the advice method
     *
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point which is passed to the advice method
     * @return mixed Result of the advice method
     */
    public function invoke(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        if ($this->runtimeEvaluator !== null && $this->runtimeEvaluator->__invoke($joinPoint) === false) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $adviceObject = $this->objectManager->get($this->aspectObjectName);
        $methodName = $this->adviceMethodName;
        return $adviceObject->$methodName($joinPoint);
    }
}
