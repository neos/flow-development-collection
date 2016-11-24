<?php
namespace Neos\Flow\Aop\Builder;

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
use Neos\Flow\Aop\Exception;

/**
 * An AOP interceptor code builder for methods enriched by advices.
 *
 * @Flow\Scope("singleton")
 */
class AdvicedMethodInterceptorBuilder extends AbstractMethodInterceptorBuilder
{
    /**
     * Builds interception PHP code for an adviced method
     *
     * @param string $methodName Name of the method to build an interceptor for
     * @param array $interceptedMethods An array of method names and their meta information, including advices for the method (if any)
     * @param string $targetClassName Name of the target class to build the interceptor for
     * @return string PHP code of the interceptor
     * @throws Exception
     */
    public function build($methodName, array $interceptedMethods, $targetClassName)
    {
        if ($methodName === '__construct') {
            throw new Exception('The ' . __CLASS__ . ' cannot build constructor interceptor code.', 1173107446);
        }

        $declaringClassName = $interceptedMethods[$methodName]['declaringClassName'];
        $proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod($methodName);
        if ($declaringClassName !== $targetClassName) {
            $proxyMethod->setMethodParametersCode($proxyMethod->buildMethodParametersCode($declaringClassName, $methodName, true));
        }

        $groupedAdvices = $interceptedMethods[$methodName]['groupedAdvices'];
        $advicesCode = $this->buildAdvicesCode($groupedAdvices, $methodName, $targetClassName, $declaringClassName);

        if ($methodName !== null || $methodName === '__wakeup') {
            $proxyMethod->addPreParentCallCode('
        if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode[\'' . $methodName . '\'])) {
');
            $proxyMethod->addPostParentCallCode('
        } else {
            $this->Flow_Aop_Proxy_methodIsInAdviceMode[\'' . $methodName . '\'] = TRUE;
            try {
            ' . $advicesCode . '
            } catch (\Exception $exception) {
                unset($this->Flow_Aop_Proxy_methodIsInAdviceMode[\'' . $methodName . '\']);
                throw $exception;
            }
            unset($this->Flow_Aop_Proxy_methodIsInAdviceMode[\'' . $methodName . '\']);
        }
');
        }
    }
}
