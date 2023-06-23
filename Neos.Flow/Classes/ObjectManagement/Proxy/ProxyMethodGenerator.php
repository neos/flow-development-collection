<?php
namespace Neos\Flow\ObjectManagement\Proxy;

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

/**
 * Generator for proxy methods
 */
class ProxyMethodGenerator extends MethodGenerator
{
    protected string $addedPreParentCallCode = '';
    protected string $addedPostParentCallCode = '';

    protected string $fullOriginalClassName = '';

    public static function fromReflection(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): static
    {
        $instance = parent::fromReflection($reflectionMethod);
        assert($instance instanceof static);
        $instance->fullOriginalClassName = $reflectionMethod->getDeclaringClass()->getName();
        return $instance;
    }

    public static function copyMethodSignature(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): static
    {
        $instance = parent::copyMethodSignature($reflectionMethod);
        assert($instance instanceof static);
        $instance->fullOriginalClassName = $reflectionMethod->getDeclaringClass()->getName();
        return $instance;
    }

    public function getFullOriginalClassName(): string
    {
        return $this->fullOriginalClassName;
    }

    public function setFullOriginalClassName(string $fullOriginalClassName): void
    {
        $this->fullOriginalClassName = $fullOriginalClassName;
    }

    /**
     * Adds PHP code to the body of this proxy method which will be executed before a possible parent call.
     */
    public function addPreParentCallCode(string $code): void
    {
        $this->addedPreParentCallCode .= rtrim($code) . PHP_EOL;
    }

    /**
     * Adds PHP code to the body of this proxy method which will be executed after a possible parent call.
     */
    public function addPostParentCallCode(string $code): void
    {
        $this->addedPostParentCallCode .= rtrim($code) . PHP_EOL;
    }

    public function generate(): string
    {
        if ($this->body === '') {
            $this->body = $this->renderBodyCode();
        }
        return parent::generate();
    }

    public function renderBodyCode(): string
    {
        if ((trim($this->addedPreParentCallCode) === '' && trim($this->addedPostParentCallCode) === '')) {
            return '';
        }

        $callParentMethodCode = $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->name);
        $returnTypeIsVoidOrNever = ((string)$this->getReturnType() === 'void' || (string)$this->getReturnType() === 'never' );
        $code = $this->addedPreParentCallCode;
        if ($this->addedPostParentCallCode !== '') {
            if ($returnTypeIsVoidOrNever) {
                if ($callParentMethodCode !== '') {
                    $code .= '    ' . $callParentMethodCode;
                }
            } else {
                $code .= '$result = ' . ($callParentMethodCode === '' ? "null;\n" : $callParentMethodCode);
            }
            $code .= $this->addedPostParentCallCode;
            if (!$returnTypeIsVoidOrNever) {
                $code .= "return \$result;\n";
            }
        } elseif (!$returnTypeIsVoidOrNever && $callParentMethodCode !== '') {
            $code .= 'return ' . $callParentMethodCode . ";\n";
        }
        return $code;
    }

    /**
     * Tells if enough code was provided (yet) so that this method would actually be rendered
     * if render() is called.
     *
     * @return bool true if there is any code to render, otherwise false
     */
    public function willBeRendered(): bool
    {
        return ($this->addedPreParentCallCode !== '' || $this->addedPostParentCallCode !== '' || $this->body !== '');
    }

    /**
     * Builds the PHP code for the parameters of the specified method to be
     * used in a method interceptor in the proxy class
     *
     * @param string|null $fullClassName Name of the class the method is declared in
     * @param string|null $methodName Name of the method to create the parameters code for
     * @param bool $addTypeAndDefaultValue If the type and default value for each parameter should be rendered
     * @return string A comma separated list of parameters
     */
    public function buildMethodParametersCode(?string $fullClassName, ?string $methodName, bool $addTypeAndDefaultValue): string
    {
        if ($fullClassName === null || $methodName === null) {
            return '';
        }

        if (!method_exists($fullClassName, $methodName)) {
            return '';
        }

        $methodParametersCode = '';
        $parameterOutput = [];
        $parameters = $this->getParameters();
        if (!empty($parameters)) {
            foreach ($parameters as $parameter) {
                if ($addTypeAndDefaultValue) {
                    $parameterOutput[] = $parameter->generate();
                } else {
                    $parameterOutput[] = '$' . $parameter->getName();
                }
            }
            $methodParametersCode .= implode(', ', $parameterOutput);
        }

        return $methodParametersCode;
    }

    /**
     * Builds PHP code which calls the original (i.e. parent) method after the added code has been executed.
     *
     * @param string $fullClassName Fully qualified name of the original class
     * @param string $methodName Name of the original method
     * @return string PHP code
     */
    protected function buildCallParentMethodCode(string $fullClassName, string $methodName): string
    {
        if (!method_exists($fullClassName, $methodName)) {
            return '';
        }
        return 'parent::' . $methodName . '(' . $this->buildMethodParametersCode($fullClassName, $methodName, false) . ");\n";
    }
}
