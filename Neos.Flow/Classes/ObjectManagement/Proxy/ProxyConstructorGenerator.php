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
use Neos\Flow\ObjectManagement\DependencyInjection\ProxyClassBuilder;

final class ProxyConstructorGenerator extends ProxyMethodGenerator
{
    private ?string $originalVisibility = null;
//
//    public function __construct($name = null, array $parameters = [], $flags = self::FLAG_PUBLIC, $body = null, $docBlock = null)
//    {
//        if ($docBlock === null) {
//            $docBlock = new DocBlockGenerator();
//        }
//        $docBlock->setSourceContent(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT . PHP_EOL . PHP_EOL . $docBlock->getSourceContent());
//        $docBlock->setWordWrap(false);
//        $docBlock->setSourceDirty(false);
//        parent::__construct('__construct', $parameters, $flags, $body, $docBlock);
//    }
//
//    public static function fromReflection(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): static
//    {
//        $method = new static('__construct');
//        $declaringClass = $reflectionMethod->getDeclaringClass();
//
//        $method->fullOriginalClassName = $declaringClass->name;
//        $method->setFinal($reflectionMethod->isFinal());
//
//        # Safe the original visibility of the constructor for later use in the proxy constructor
//        if ($reflectionMethod->isPrivate()) {
//            $method->originalVisibility = self::VISIBILITY_PRIVATE;
//        } elseif ($reflectionMethod->isProtected()) {
//            $method->originalVisibility = self::VISIBILITY_PROTECTED;
//        }
//        $method->setVisibility(self::VISIBILITY_PUBLIC);
//
//        if (!empty($reflectionMethod->getDocComment())) {
//            $docBlock = DocBlockGenerator::fromReflection($reflectionMethod->getDocBlock());
//            $docBlock->setSourceContent(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT . PHP_EOL . PHP_EOL . $docBlock->getSourceContent());
//        } else {
//            $docBlock = new DocBlockGenerator();
//            $docBlock->setSourceContent(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT);
//        }
//        $docBlock->setWordWrap(false);
//        $docBlock->setSourceDirty(false);
//        $method->setDocBlock($docBlock);
//        return $method;
//    }
//
//    /**
//     * @throws \BadMethodCallException
//     */
//    public static function copyMethodSignature(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): never
//    {
//        throw new \BadMethodCallException('copyMethodSignature() is not supported, nor needed for constructor proxies.', 1685078402);
//    }

    /** @phpstan-ignore constructor.unusedParameter */
    public function __construct($name = null, array $parameters = [], $flags = self::FLAG_PUBLIC, $body = null, $docBlock = null)
    {
        if ($docBlock === null) {
            $docBlock = new DocBlockGenerator();
        }
        $docBlock->setSourceContent(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT . PHP_EOL . PHP_EOL . $docBlock->getSourceContent());
        $docBlock->setWordWrap(false);
        $docBlock->setSourceDirty(false);
        parent::__construct('__construct', $parameters, $flags, $body, $docBlock);
    }

    public static function fromReflection(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): static
    {
        $method = new static('__construct');
        $declaringClass = $reflectionMethod->getDeclaringClass();

        $method->fullOriginalClassName = $declaringClass->getName();
        $method->setFinal($reflectionMethod->isFinal());

        # Safe the original visibility of the constructor for later use in the proxy constructor
        if ($reflectionMethod->isPrivate()) {
            $method->originalVisibility = self::VISIBILITY_PRIVATE;
        } elseif ($reflectionMethod->isProtected()) {
            $method->originalVisibility = self::VISIBILITY_PROTECTED;
        }
        $method->setVisibility(self::VISIBILITY_PUBLIC);

        if (!empty($reflectionMethod->getDocComment())) {
            $docBlock = DocBlockGenerator::fromReflection($reflectionMethod->getDocBlock());
            $docBlock->setSourceContent(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT . PHP_EOL . PHP_EOL . $docBlock->getSourceContent());
        } else {
            $docBlock = new DocBlockGenerator();
            $docBlock->setSourceContent(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT);
        }
        $docBlock->setWordWrap(false);
        $docBlock->setSourceDirty(false);
        $method->setDocBlock($docBlock);
        return $method;
    }

    /**
     * @throws \BadMethodCallException
     */
    public static function copyMethodSignature(\Laminas\Code\Reflection\MethodReflection $reflectionMethod): never
    {
        throw new \BadMethodCallException('copyMethodSignature() is not supported, nor needed for constructor proxies.', 1685078402);
    }

    public function getOriginalVisibility(): ?string
    {
        return $this->originalVisibility;
    }

    public function renderBodyCode(): string
    {
        if ((trim($this->addedPreParentCallCode) === '' && trim($this->addedPostParentCallCode) === '')) {
            return '';
        }

        $callParentMethodCode = isset($this->fullOriginalClassName) ? $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->name) : '';
        $this->addedPreParentCallCode = rtrim($this->addedPreParentCallCode);
        $this->addedPostParentCallCode = rtrim($this->addedPostParentCallCode);

        return
            ($callParentMethodCode !== '' && $this->originalVisibility !== null ? $this->buildEnforceVisibilityCode($this->originalVisibility) : '') .
            ($callParentMethodCode !== '' ? $this->buildAssignMethodArgumentsCode() : '') .
            ($this->addedPreParentCallCode !== '' ? $this->addedPreParentCallCode . PHP_EOL : '') .
            $callParentMethodCode .
            ($this->addedPostParentCallCode !== '' ? $this->addedPostParentCallCode . PHP_EOL : '');
    }

    protected function buildAssignMethodArgumentsCode(): string
    {
        return '$arguments = func_get_args();' . PHP_EOL;
    }

    /**
     * Build code which calls the parent method, if any.
     *
     * Note that $fullClassName is the original, non-proxied class and $parentClassName is the
     * name of a potential non-proxied parent class of the original class.
     *
     * The context where the parent:: call is made is a proxy class. Therefore, the call will
     *
     *  - either call the method in the original class of the current proxy, if it exists, or
     *  - call the original method in the parent class or
     *  - call the proxied method in the parent class or
     *  - call the original method in a subclass or
     *  - call the proxied method in a subclass
     *
     * See the ProxyCompilerTest functional tests for examples of these cases.
     *
     * @param class-string $fullClassName
     */
    protected function buildCallParentMethodCode(string $fullClassName, string $methodName): string
    {
        $parentClassName = get_parent_class($fullClassName);
        if (
            !method_exists($fullClassName, $methodName) &&
            ($parentClassName === false || !method_exists($parentClassName, $methodName))
        ) {
            return '';
        }
        return "parent::{$methodName}(...\$arguments);" . PHP_EOL;
    }

    /**
     * Build code which enforces the original visibility of the constructor.
     *
     * This code is added to the beginning of the constructor body of a proxy class if
     * the original visibility was not "public".
     *
     * For private or protected constructors there are two cases which are allowed:
     *
     * 1) The constructor is called from within the original class itself
     * 2) The constructor is called from within a subclass of the original class
     *
     * In all other cases, an exception is thrown which is similar to the fatal error
     * PHP would throw in that case.
     */
    private function buildEnforceVisibilityCode(string $originalVisibility): string
    {
        if ($originalVisibility === self::VISIBILITY_PUBLIC) {
            return '';
        }
        $originalVisibilityString = $originalVisibility === self::VISIBILITY_PROTECTED ? 'protected' : 'private';
        $fullOriginalClassNameWithSuffix = $this->fullOriginalClassName . Compiler::ORIGINAL_CLASSNAME_SUFFIX;
        return <<<PHP
        \$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (isset(\$backtrace[1]) &&
            !is_subclass_of(\$backtrace[1]['class'], \\{$this->fullOriginalClassName}::class) &&
            !is_subclass_of(\\{$this->fullOriginalClassName}::class, \$backtrace[1]['class']) &&
            \$backtrace[1]['class'] !== '{$this->fullOriginalClassName}' &&
            \$backtrace[1]['class'] !== '{$fullOriginalClassNameWithSuffix}'
        ) {
            throw new \\Error('Call to {$originalVisibilityString} {$this->fullOriginalClassName}::__construct() from invalid context', 1686153840);
        }
        PHP . PHP_EOL;
    }
}
