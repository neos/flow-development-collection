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
 * A method interceptor build for constructors with advice.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class AdvisedConstructorInterceptorBuilder extends AbstractMethodInterceptorBuilder
{
    /**
     * Builds interception PHP code for an advised constructor
     *
     * @param string $methodName Name of the method to build an interceptor for
     * @param array $methodMetaInformation An array of method names and their meta information, including advices for the method (if any)
     * @param string $targetClassName Name of the target class to build the interceptor for
     * @return void
     * @throws Exception
     */
    public function build(string $methodName, array $methodMetaInformation, string $targetClassName): void
    {
        if ($methodName !== '__construct') {
            throw new Exception('The ' . __CLASS__ . ' can only build constructor interceptor code.', 1231789021);
        }

        $declaringClassName = $methodMetaInformation[$methodName]['declaringClassName'];
        $proxyMethod = $this->compiler->getProxyClass($targetClassName)->getConstructor();

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
            return;
        }
        PHP);
    }
}
