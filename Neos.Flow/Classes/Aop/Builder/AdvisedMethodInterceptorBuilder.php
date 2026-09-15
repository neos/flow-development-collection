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
     * @param array<string,mixed> $methodMetaInformation An array of method names and their meta information, including advices for the method (if any)
     * @param class-string $targetClassName Name of the target class to build the interceptor for
     * @return void
     * @throws Exception
     */
    public function build(string $methodName, array $methodMetaInformation, string $targetClassName): void
    {
        if ($methodName === '__construct') {
            throw new Exception(sprintf('The %s cannot build constructor interceptor code.', __CLASS__), 1173107446);
        }

        $declaringClassName = $methodMetaInformation[$methodName]['declaringClassName'];
        $declaredReturnType = ($declaringClassName !== null) ? $this->reflectionService->getMethodDeclaredReturnType($declaringClassName, $methodName) : null;
        $proxyClass = $this->compiler->getProxyClass($targetClassName);
        if ($proxyClass === false) {
            throw new Exception(sprintf('The class %s does not exist or no proxy could be built.', $targetClassName), 1784536964);
        }
        $proxyMethod = $proxyClass->getMethod($methodName);
        if ($proxyMethod->getVisibility() === ProxyMethodGenerator::VISIBILITY_PRIVATE) {
            throw new Exception(sprintf('The %s cannot build interceptor code for private method %s::%s(). Please change the scope to at least protected or adjust the pointcut expression in the corresponding aspect.', __CLASS__, $targetClassName, $methodName), 1593070574);
        }
        if ($declaringClassName !== $targetClassName) {
            $originalMethod = MethodGenerator::copyMethodSignature(new \Laminas\Code\Reflection\MethodReflection($declaringClassName, $methodName));
            $proxyMethod->setParameters($originalMethod->getParameters());
            $methodReturnsReference = $originalMethod->returnsReference();
        } else {
            $methodReturnsReference = $proxyMethod->returnsReference();
        }
        if ($methodReturnsReference) {
            throw new Exception(sprintf('The %s cannot build interceptor code for method %s::%s() because it returns by reference: references cannot be preserved through an advice chain, so the advised method would silently return a copy instead of the original reference. Please remove the advice for this method, or exclude the class from proxying by adding a #[Flow\Proxy(false)] attribute.', __CLASS__, $targetClassName, $methodName), 1785837971);
        }

        $groupedAdvices = $methodMetaInformation[$methodName]['groupedAdvices'];
        $advicesCode = $this->buildAdvicesCode($groupedAdvices, $methodName, $targetClassName, $declaringClassName, $declaredReturnType);
        $neverThrowCode = $declaredReturnType === 'never' ? 'throw new \RuntimeException(\'Possible bug in around advice proxy code for method ' . $targetClassName . '::' . $methodName . '() with return type "never". This point should never be reached. 👻\', 1761038455);' : '';

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
        {$neverThrowCode}
        PHP);
    }
}
