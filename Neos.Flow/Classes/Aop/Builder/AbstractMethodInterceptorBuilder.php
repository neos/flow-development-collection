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
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\Reflection\ReflectionService;

/**
 * An abstract class with builder functions for AOP method interceptors code
 * builders.
 *
 */
abstract class AbstractMethodInterceptorBuilder
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var Compiler
     */
    protected $compiler;

    /**
     * Injects the reflection service
     *
     * @param ReflectionService $reflectionService The reflection service
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param Compiler $compiler
     * @return void
     */
    public function injectCompiler(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Builds method interception PHP code
     *
     * @param string $methodName Name of the method to build an interceptor for
     * @param array $methodMetaInformation An array of method names and their meta information, including advices for the method (if any)
     * @param string $targetClassName Name of the target class to build the interceptor for
     * @return string PHP code of the interceptor
     */
    abstract public function build($methodName, array $methodMetaInformation, $targetClassName);

    /**
     * Builds a string containing PHP code to build the array given as input.
     *
     * @param array $array
     * @return string e.g. 'array()' or 'array(1 => 'bar')
     */
    protected function buildArraySetupCode(array $array)
    {
        $code = 'array(';
        foreach ($array as $key => $value) {
            $code .= (is_string($key)) ? "'" . $key . "'" : $key;
            $code .= ' => ';
            if ($value === null) {
                $code .= 'NULL';
            } elseif (is_bool($value)) {
                $code .= ($value ? 'TRUE' : 'FALSE');
            } elseif (is_numeric($value)) {
                $code .= $value;
            } elseif (is_string($value)) {
                $code .= "'" . $value . "'";
            }
            $code .= ', ';
        }
        return rtrim($code, ', ') . ')';
    }

    /**
     * Builds the PHP code for the method arguments array which is passed to
     * the constructor of a new join point. Used in the method interceptor
     * functions.
     *
     * @param string $className Name of the declaring class of the method
     * @param string $methodName Name of the method to create arguments array code for
     * @param boolean $useArgumentsArray If set, the $methodArguments array will be built from $arguments instead of using the actual parameter variables.
     * @return string The generated code to be used in an "array()" definition
     */
    protected function buildMethodArgumentsArrayCode($className, $methodName, $useArgumentsArray = false)
    {
        if ($className === null || $methodName === null) {
            return '';
        }

        $argumentsArrayCode = "\n                \$methodArguments = [];\n";

        $methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
        if (count($methodParameters) > 0) {
            $argumentsArrayCode .= "\n";
            $argumentIndex = 0;
            foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
                if ($useArgumentsArray) {
                    $argumentsArrayCode .= "                if (array_key_exists(" . $argumentIndex . ", \$arguments)) \$methodArguments['" . $methodParameterName . "'] = \$arguments[" . $argumentIndex . "];\n";
                } else {
                    $argumentsArrayCode .= "                \$methodArguments['" . $methodParameterName . "'] = ";
                    $argumentsArrayCode .= $methodParameterInfo['byReference'] ? '&' : '';
                    $argumentsArrayCode .= '$' . $methodParameterName . ";\n";
                }
                $argumentIndex ++;
            }
            $argumentsArrayCode .= "            ";
        }
        return $argumentsArrayCode;
    }

    /**
     * Generates the parameters code needed to call the constructor with the saved parameters.
     *
     * @param string $className Name of the class the method is declared in
     * @return string The generated parameters code
     */
    protected function buildSavedConstructorParametersCode($className)
    {
        if ($className === null) {
            return '';
        }

        $parametersCode = '';
        $methodParameters = $this->reflectionService->getMethodParameters($className, '__construct');
        $methodParametersCount = count($methodParameters);
        if ($methodParametersCount > 0) {
            foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
                $methodParametersCount--;
                $parametersCode .= '$this->Flow_Aop_Proxy_originalConstructorArguments[\'' . $methodParameterName . '\']' . ($methodParametersCount > 0 ? ', ' : '');
            }
        }
        return $parametersCode;
    }

    /**
     * Builds the advice interception code, to be used in a method interceptor.
     *
     * @param array $groupedAdvices The advices grouped by advice type
     * @param string $methodName Name of the method the advice applies to
     * @param string $targetClassName Name of the target class
     * @param string $declaringClassName Name of the declaring class. This is usually the same as the $targetClassName. However, it is the introduction interface for introduced methods.
     * @return string PHP code to be used in the method interceptor
     */
    protected function buildAdvicesCode(array $groupedAdvices, $methodName, $targetClassName, $declaringClassName)
    {
        $advicesCode = $this->buildMethodArgumentsArrayCode($declaringClassName, $methodName, ($methodName === '__construct'));

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterThrowingAdvice::class]) || isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterAdvice::class])) {
            $advicesCode .= "\n        \$result = NULL;\n        \$afterAdviceInvoked = FALSE;\n        try {\n";
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\BeforeAdvice::class])) {
            $advicesCode .= '
                if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\BeforeAdvice\'])) {
                    $advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\BeforeAdvice\'];
                    $joinPoint = new \Neos\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments);
                    foreach ($advices as $advice) {
                        $advice->invoke($joinPoint);
                    }

                    $methodArguments = $joinPoint->getMethodArguments();
                }
';
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AroundAdvice::class])) {
            $advicesCode .= '
                $adviceChains = $this->Flow_Aop_Proxy_getAdviceChains(\'' . $methodName . '\');
                $adviceChain = $adviceChains[\'Neos\Flow\Aop\Advice\AroundAdvice\'];
                $adviceChain->rewind();
                $joinPoint = new \Neos\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, $adviceChain);
                $result = $adviceChain->proceed($joinPoint);
                $methodArguments = $joinPoint->getMethodArguments();
';
        } else {
            $advicesCode .= '
                $joinPoint = new \Neos\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments);
                $result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);
                $methodArguments = $joinPoint->getMethodArguments();
';
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterReturningAdvice::class])) {
            $advicesCode .= '
                if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterReturningAdvice\'])) {
                    $advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterReturningAdvice\'];
                    $joinPoint = new \Neos\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, $result);
                    foreach ($advices as $advice) {
                        $advice->invoke($joinPoint);
                    }

                    $methodArguments = $joinPoint->getMethodArguments();
                }
';
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterAdvice::class])) {
            $advicesCode .= '
                if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterAdvice\'])) {
                    $advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterAdvice\'];
                    $joinPoint = new \Neos\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, $result);
                    $afterAdviceInvoked = TRUE;
                    foreach ($advices as $advice) {
                        $advice->invoke($joinPoint);
                    }

                    $methodArguments = $joinPoint->getMethodArguments();
                }
';
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterThrowingAdvice::class]) || isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterAdvice::class])) {
            $advicesCode .= '
            } catch (\Exception $exception) {
';
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterThrowingAdvice::class])) {
            $advicesCode .= '
                if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterThrowingAdvice\'])) {
                    $advices =  $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterThrowingAdvice\'];
                    $joinPoint = new \Neos\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, NULL, $exception);
                    foreach ($advices as $advice) {
                        $advice->invoke($joinPoint);
                    }

                    $methodArguments = $joinPoint->getMethodArguments();
                }
';
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterAdvice::class])) {
            $advicesCode .= '
                if (!$afterAdviceInvoked && isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterAdvice\'])) {
                    $advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'Neos\Flow\Aop\Advice\AfterAdvice\'];
                    $joinPoint = new \Neos\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, NULL, $exception);
                    foreach ($advices as $advice) {
                        $advice->invoke($joinPoint);
                    }
                }
';
        }

        if (isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterThrowingAdvice::class]) || isset($groupedAdvices[\Neos\Flow\Aop\Advice\AfterAdvice::class])) {
            $advicesCode .= '
                throw $exception;
        }
';
        }

        return $advicesCode;
    }
}
