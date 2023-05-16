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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Flow\Reflection\ReflectionService;

/**
 * Representation of a method within a proxy class
 *
 * @Flow\Proxy(false)
 */
class ProxyMethod
{
    /**
     * Fully qualified class name of the original class
     *
     * @var string
     * @psalm-var class-string
     */
    protected $fullOriginalClassName;

    /**
     * Name of the original method
     *
     * @var string
     */
    protected $methodName;

    /**
     * Visibility of the method
     *
     * @var string
     */
    protected $visibility;

    /**
     * @var string
     */
    protected $addedPreParentCallCode = '';

    /**
     * @var string
     */
    protected $addedPostParentCallCode = '';

    /**
     * @var string
     */
    protected $methodParametersCode = '';

    /**
     * @var string
     */
    public $methodBody = '';

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Constructor
     *
     * @param string $fullOriginalClassName The fully qualified class name of the original class
     * @param string $methodName Name of the proxy (and original) method
     * @psalm-param class-string $fullOriginalClassName
     */
    public function __construct(string $fullOriginalClassName, string $methodName)
    {
        $this->fullOriginalClassName = $fullOriginalClassName;
        $this->methodName = $methodName;
    }

    /**
     * Injects the Reflection Service
     *
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Overrides the method's visibility
     *
     * @param string $visibility One of 'public', 'protected', 'private'
     * @return void
     */
    public function overrideMethodVisibility(string $visibility): void
    {
        $this->visibility = $visibility;
    }

    /**
     * Returns TRUE if this proxy belongs to a private method, otherwise FALSE
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->getMethodVisibilityString() === 'private';
    }

    /**
     * Adds PHP code to the body of this method which will be executed before a possible parent call.
     *
     * @param string $code
     * @return void
     */
    public function addPreParentCallCode(string $code): void
    {
        $this->addedPreParentCallCode .= $code;
    }

    /**
     * Adds PHP code to the body of this method which will be executed after a possible parent call.
     *
     * @param string $code
     * @return void
     */
    public function addPostParentCallCode(string $code): void
    {
        $this->addedPostParentCallCode .= $code;
    }

    /**
     * Sets the (exact) code which use used in as the parameters signature for this method.
     *
     * @param string $code Parameters code, for example: '$foo, array $bar, \Foo\Bar\Baz $baz'
     * @return void
     */
    public function setMethodParametersCode(string $code): void
    {
        $this->methodParametersCode = $code;
    }

    /**
     * Renders the PHP code for this Proxy Method
     *
     * @return string PHP code
     */
    public function render(): string
    {
        $methodDocumentation = $this->buildMethodDocumentation($this->fullOriginalClassName, $this->methodName);
        $methodParametersCode = ($this->methodParametersCode !== '' ? $this->methodParametersCode : $this->buildMethodParametersCode($this->fullOriginalClassName, $this->methodName));
        $callParentMethodCode = $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->methodName);

        $finalKeyword = $this->reflectionService->isMethodFinal($this->fullOriginalClassName, $this->methodName) ? 'final ' : '';
        $staticKeyword = $this->reflectionService->isMethodStatic($this->fullOriginalClassName, $this->methodName) ? 'static ' : '';

        $returnType = $this->reflectionService->getMethodDeclaredReturnType($this->fullOriginalClassName, $this->methodName);
        $returnTypeIsVoid = $returnType === 'void';
        $returnTypeDeclaration = ($returnType !== null ? ' : ' . $returnType : '');


        $code = '';
        if ($this->addedPreParentCallCode !== '' || $this->addedPostParentCallCode !== '' || $this->methodBody !== '') {
            $code = "\n" .
                $methodDocumentation .
                '    ' . $finalKeyword . $staticKeyword . $this->getMethodVisibilityString() . ' function ' . $this->methodName . '(' . $methodParametersCode . ")$returnTypeDeclaration\n    {\n";
            if ($this->methodBody !== '') {
                $code .= "\n" . $this->methodBody . "\n";
            } else {
                $code .= $this->addedPreParentCallCode;
                if ($this->addedPostParentCallCode !== '') {
                    if ($returnTypeIsVoid) {
                        if ($callParentMethodCode !== '') {
                            $code .= '            ' . $callParentMethodCode;
                        }
                    } else {
                        $code .= '        $result = ' . ($callParentMethodCode === '' ? "NULL;\n" : $callParentMethodCode);
                    }
                    $code .= $this->addedPostParentCallCode;
                    if (!$returnTypeIsVoid) {
                        $code .= "        return \$result;\n";
                    }
                } elseif (!$returnTypeIsVoid && $callParentMethodCode !== '') {
                    $code .= '        return ' . $callParentMethodCode . ";\n";
                }
            }
            $code .= "    }\n";
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
        return ($this->addedPreParentCallCode !== '' || $this->addedPostParentCallCode !== '');
    }

    /**
     * Builds the method documentation block for the specified method keeping the vital annotations
     *
     * @param string $className Name of the class the method is declared in
     * @param string $methodName Name of the method to create the parameters code for
     * @return string $methodDocumentation DocComment for the given method
     * @psalm-param class-string $className
     */
    protected function buildMethodDocumentation(string $className, string $methodName): string
    {
        $methodDocumentation = "    /**\n     * Autogenerated Proxy Method\n";

        if ($this->reflectionService->hasMethod($className, $methodName)) {
            $method = new MethodReflection($className, $methodName);
            $docComment = $method->getDocComment();
            if ($docComment !== false) {
                $methodDocumentation .= preg_replace('/\/\*\*[^\n]*\n/', "     *\n", $docComment) . "\n";
            } else {
                $methodDocumentation .= "     */\n";
            }

            if (PHP_MAJOR_VERSION >= 8) {
                $method = new MethodReflection($className, $methodName);
                foreach ($method->getAttributes() as $attribute) {
                    $methodDocumentation .= '    ' . Compiler::renderAttribute($attribute) . "\n";
                }
            }
        } else {
            $methodDocumentation .= "     */\n";
        }
        return $methodDocumentation;
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
    public function buildMethodParametersCode(?string $fullClassName, ?string $methodName, bool $addTypeAndDefaultValue = true): string
    {
        $methodParametersCode = '';
        $methodParameterTypeName = '';
        $nullableSign = '';
        $defaultValue = '';
        $byReferenceSign = '';

        if ($fullClassName === null || $methodName === null) {
            return '';
        }

        $methodParameters = $this->reflectionService->getMethodParameters($fullClassName, $methodName);
        if (count($methodParameters) > 0) {
            $methodParametersCount = 0;
            foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
                if ($addTypeAndDefaultValue) {
                    if ($methodParameterInfo['array'] === true) {
                        $methodParameterTypeName = 'array';
                    } elseif ($methodParameterInfo['scalarDeclaration']) {
                        $methodParameterTypeName = $methodParameterInfo['type'];
                    } elseif ($methodParameterInfo['class'] !== null) {
                        $methodParameterTypeName = '\\' . $methodParameterInfo['class'];
                    } else {
                        $methodParameterTypeName = '';
                    }
                    $nullableSign = $methodParameterInfo['allowsNull'] ? '?' : '';
                    if ($methodParameterInfo['optional'] === true) {
                        $rawDefaultValue = $methodParameterInfo['defaultValue'] ?? null;
                        if ($rawDefaultValue === null) {
                            $defaultValue = ' = NULL';
                        } elseif (is_bool($rawDefaultValue)) {
                            $defaultValue = ($rawDefaultValue ? ' = true' : ' = false');
                        } elseif (is_string($rawDefaultValue)) {
                            $defaultValue = " = '" . $rawDefaultValue . "'";
                        } elseif (is_numeric($rawDefaultValue)) {
                            $defaultValue = ' = ' . $rawDefaultValue;
                        } elseif (is_array($rawDefaultValue)) {
                            $defaultValue = ' = ' . $this->buildArraySetupCode($rawDefaultValue);
                        }
                    }
                    $byReferenceSign = ($methodParameterInfo['byReference'] ? '&' : '');
                }

                $methodParametersCode .= ($methodParametersCount > 0 ? ', ' : '')
                    . ($methodParameterTypeName ? $nullableSign . $methodParameterTypeName . ' ' : '')
                    . $byReferenceSign
                    . '$'
                    . $methodParameterName
                    . $defaultValue
                ;
                $methodParametersCount++;
            }
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
        if (!$this->reflectionService->hasMethod($fullClassName, $methodName)) {
            return '';
        }
        return 'parent::' . $methodName . '(' . $this->buildMethodParametersCode($fullClassName, $methodName, false) . ");\n";
    }

    /**
     * Builds a string containing PHP code to build the array given as input.
     *
     * @param array $array
     * @return string e.g. 'array()' or 'array(1 => 'bar')
     */
    protected function buildArraySetupCode(array $array): string
    {
        $code = 'array(';
        foreach ($array as $key => $value) {
            $code .= (is_string($key)) ? "'" . $key . "'" : $key;
            $code .= ' => ';
            if ($value === null) {
                $code .= 'NULL';
            } elseif (is_bool($value)) {
                $code .= ($value ? 'true' : 'false');
            } elseif (is_string($value)) {
                $code .= "'" . $value . "'";
            } elseif (is_numeric($value)) {
                $code .= $value;
            }
            $code .= ', ';
        }
        return rtrim($code, ', ') . ')';
    }

    /**
     * Returns the method's visibility string found by the reflection service
     * Note: If the reflection service has no information about this method,
     * 'public' is returned.
     *
     * @return string One of 'public', 'protected' or 'private'
     */
    protected function getMethodVisibilityString(): string
    {
        if ($this->visibility !== null) {
            return $this->visibility;
        }
        if ($this->reflectionService->isMethodProtected($this->fullOriginalClassName, $this->methodName)) {
            return 'protected';
        }
        if ($this->reflectionService->isMethodPrivate($this->fullOriginalClassName, $this->methodName)) {
            return 'private';
        }
        return 'public';
    }

    /**
     * Override the method body
     *
     * @param string $methodBody
     * @return void
     */
    public function setMethodBody(string $methodBody): void
    {
        $this->methodBody = $methodBody;
    }
}
