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

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;

/**
 * Class ProxyMethodGenerator
 *
 * This class is responsible for generating proxy methods that can be used as method interceptors.
 * It extends the MethodGenerator class.
 */
class ProxyMethodGenerator extends MethodGenerator
{
    protected string $addedPreParentCallCode = '';
    protected string $addedPostParentCallCode = '';
    protected string $attributesCode = '';

    /** @var class-string|null */
    protected ?string $fullOriginalClassName = null;

    public static function fromReflection(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): static
    {
        $instance = parent::fromReflection($reflectionMethod);
        assert($instance instanceof static);
        $instance->fullOriginalClassName = $reflectionMethod->getDeclaringClass()->getName();
        $instance->attributesCode = $instance->buildAttributesCode($reflectionMethod);
        return $instance;
    }

    public static function copyMethodSignatureAndDocblock(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): static
    {
        $instance = parent::copyMethodSignature($reflectionMethod);
        assert($instance instanceof static);
        if ($reflectionMethod->getDocComment() !== false) {
            $instance->setDocBlock(DocBlockGenerator::fromReflection($reflectionMethod->getDocBlock()));
        }
        $instance->fullOriginalClassName = $reflectionMethod->getDeclaringClass()->getName();
        $instance->attributesCode = $instance->buildAttributesCode($reflectionMethod);
        return $instance;
    }

    public function getFullOriginalClassName(): ?string
    {
        return $this->fullOriginalClassName;
    }

    /**
     * @param class-string $fullOriginalClassName
     */
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

    /**
     * Generates the code for the method.
     *
     * This method overrides the parent generate() method in order to insert attributes code. As soon
     * as https://github.com/laminas/laminas-code/pull/145 is merged and released, this can be
     * implemented properly.
     *
     * @return string The generated method code.
     */
    public function generate(): string
    {
        if ($this->body === '') {
            $this->body = $this->renderBodyCode();
        }

        if ($this->body === '') {
            return '';
        }

        $output = '';

        $indent = $this->getIndentation();

        if (($docBlock = $this->getDocBlock()) !== null) {
            $docBlock->setIndentation($indent);
            $output .= $docBlock->generate();
        }

        $output .= $this->attributesCode;
        $output .= $indent;

        if ($this->isAbstract()) {
            $output .= 'abstract ';
        } else {
            $output .= $this->isFinal() ? 'final ' : '';
        }

        $output .= $this->getVisibility()
            . ($this->isStatic() ? ' static' : '')
            . ' function '
            . ($this->returnsReference() ? '& ' : '')
            . $this->getName() . '(';

        $output .= implode(', ', array_map(
            static fn(ParameterGenerator $parameter): string => $parameter->generate(),
            $this->getParameters()
        ));

        $output .= ')';

        if ($this->getReturnType()) {
            $output .= ' : ' . $this->getReturnType()->generate();
        }

        if ($this->isAbstract()) {
            return $output . ';';
        }

        if ($this->isInterface()) {
            return $output . ';';
        }

        $output .= self::LINE_FEED . $indent . '{' . self::LINE_FEED;

        if ($this->body) {
            $output .= preg_replace('#^((?![a-zA-Z0-9_-]+;).+?)$#m', $indent . $indent . '$1', trim($this->body))
                . self::LINE_FEED;
        }

        $output .= $indent . '}' . self::LINE_FEED;

        return $output;
    }

    public function renderBodyCode(): string
    {
        if ((trim($this->addedPreParentCallCode) === '' && trim($this->addedPostParentCallCode) === '')) {
            return '';
        }

        $callParentMethodCode = isset($this->fullOriginalClassName) ? $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->name) : '';
        $returnTypeIsVoidOrNever = ((string)$this->getReturnType() === 'void' || (string)$this->getReturnType() === 'never');
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
     * @param class-string|null $fullClassName Name of the class the method is declared in
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
     * @param class-string $fullClassName Fully qualified name of the original class
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

    /**
     * Build the code for the attributes of a given \ReflectionMethod object.
     *
     * Note: This is just a preliminary solution until https://github.com/laminas/laminas-code/pull/145
     *       is implemented and released.
     *
     * @param \ReflectionMethod $reflectionMethod The \ReflectionMethod object to retrieve attributes from.
     * @return string The code for the attributes of the given \ReflectionMethod object.
     */
    protected function buildAttributesCode(\ReflectionMethod $reflectionMethod): string
    {
        $indent = $this->getIndentation();
        $attributesCode = "";

        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeName = "\\" . ltrim($attribute->getName(), '\\');
            $argumentsString = $this->formatAttributesArguments($attribute->getArguments());
            $attributesCode .= "{$indent}#[{$attributeName}({$argumentsString})]" . self::LINE_FEED;
        }

        return $attributesCode;
    }

    /**
     * Formats the arguments of attributes into a string.
     *
     * @param array $arguments An array of arguments for attributes.
     *
     * @return string The formatted arguments as a string.
     */
    private function formatAttributesArguments(array $arguments): string
    {
        $formattedArguments = [];

        foreach ($arguments as $key => $value) {
            $formattedArguments[] = "{$key}: " . $this->formatAttributeValue($value);
        }

        return implode(', ', $formattedArguments);
    }

    /**
     * Formats the given attribute value.
     *
     * @param mixed $value The value to be formatted.
     * @return string The formatted attribute value.
     */
    private function formatAttributeValue(mixed $value): string
    {
        if (is_string($value)) {
            return "\"$value\"";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (is_array($value)) {
            $formattedArrayElements = implode(', ', array_map(function ($key, $value) {
                return is_int($key)
                    ? $this->formatAttributeValue($value)
                    : "\"{$key}\" => " . $this->formatAttributeValue($value);
            }, array_keys($value), $value));
            return "[{$formattedArrayElements}]";
        }

        // Fallback for any other types (shouldn't happen with PHP attributes)
        return '';
    }
}
