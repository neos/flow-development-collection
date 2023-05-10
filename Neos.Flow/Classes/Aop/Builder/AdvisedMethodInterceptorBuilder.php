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

use Laminas\Code\Generator\MethodGenerator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Exception;
use Neos\Flow\ObjectManagement\Proxy\ProxyMethodGenerator;

/**
 * An AOP interceptor code builder for methods enriched by advices.
 *
 * @Flow\Scope("singleton")
 */
class AdvisedMethodInterceptorBuilder extends AbstractMethodInterceptorBuilder
{
    /**
     * Builds interception PHP code for an advised method
     *
     * @param string $methodName Name of the method to build an interceptor for
     * @param array $methodMetaInformation An array of method names and their meta information, including advices for the method (if any)
     * @param string $targetClassName Name of the target class to build the interceptor for
     * @return void
     * @throws Exception
     */
    public function build(string $methodName, array $methodMetaInformation, string $targetClassName): void
    {
        if ($methodName === '__construct') {
            throw new Exception('The ' . __CLASS__ . ' cannot build constructor interceptor code.', 1173107446);
        }

        $declaringClassName = $methodMetaInformation[$methodName]['declaringClassName'];
        $proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod($methodName);
        if ($proxyMethod->getVisibility() === ProxyMethodGenerator::VISIBILITY_PRIVATE) {
            throw new Exception(sprintf('The %s cannot build interceptor code for private method %s::%s(). Please change the scope to at least protected or adjust the pointcut expression in the corresponding aspect.', __CLASS__, $targetClassName, $methodName), 1593070574);
        }
        if ($declaringClassName !== $targetClassName) {
            $originalMethod = MethodGenerator::copyMethodSignature(new \Laminas\Code\Reflection\MethodReflection($declaringClassName, $methodName));
            $proxyMethod->setParameters($originalMethod->getParameters());
        }

        $groupedAdvices = $methodMetaInformation[$methodName]['groupedAdvices'];
        $advicesCode = $this->buildAdvicesCode($groupedAdvices, $methodName, $targetClassName, $declaringClassName);

        $proxyMethod->addPreParentCallCode(<<<PHP
        if (isset(\$this->Flow_Aop_Proxy_methodIsInAdviceMode['{$methodName}'])) {
        PHP);
        $proxyMethod->addPostParentCallCode(<<<PHP
        } else {
            \$this->Flow_Aop_Proxy_methodIsInAdviceMode['{$methodName}'] = true;
            try {
            {$advicesCode}
            } catch (\Exception \$exception) {
                unset(\$this->Flow_Aop_Proxy_methodIsInAdviceMode['{$methodName}']);
                throw \$exception;
            }
            unset(\$this->Flow_Aop_Proxy_methodIsInAdviceMode['{$methodName}']);
        }
        PHP);
    }
}
