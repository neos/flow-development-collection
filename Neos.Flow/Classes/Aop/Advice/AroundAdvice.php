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
 * Implementation of the Around Advice.
 */
class AroundAdvice extends AbstractAdvice implements AdviceInterface
{
    /**
     * Invokes the advice method
     *
     * @param JoinPointInterface $joinPoint The current join point which is passed to the advice method
     * @return mixed Result of the advice method
     */
    public function invoke(JoinPointInterface $joinPoint)
    {
        if ($this->runtimeEvaluator !== null && $this->runtimeEvaluator->__invoke($joinPoint, $this->objectManager) === false) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $adviceObject = $this->objectManager->get($this->aspectObjectName);
        $methodName = $this->adviceMethodName;
        return $adviceObject->$methodName($joinPoint);
    }
}
