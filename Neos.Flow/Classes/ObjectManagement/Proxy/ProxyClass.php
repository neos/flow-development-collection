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

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxyTrait;
use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\ReflectionService;
use ReflectionException;

/**
 * Representation of a Proxy Class during rendering time
 *
 * @Flow\Proxy(false)
 */
class ProxyClass
{
    /**
     * Namespace, extracted from the fully qualified original class name
     *
     * @var string
     */
    protected $namespace = '';

    /**
     * The original class name
     *
     * @var string
     */
    protected $originalClassName;

    /**
     * Fully qualified class name of the original class
     *
     * @var string
     * @psalm-var class-string
     */
    protected $fullOriginalClassName;

    /**
     * @var ProxyConstructor
     */
    protected $constructor;

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @var array
     */
    protected $constants = [];

    /**
     * Note: Not using ProxyInterface::class here, since the interface names must have a leading backslash.
     *
     * @var array
     */
    protected $interfaces = ['\Neos\Flow\ObjectManagement\Proxy\ProxyInterface'];

    /**
     * @var array
     */
    protected $traits = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Creates a new ProxyClass instance.
     *
     * @param string $fullOriginalClassName The fully qualified class name of the original class
     */
    public function __construct(string $fullOriginalClassName)
    {
        if (!str_contains($fullOriginalClassName, '\\')) {
            $this->originalClassName = $fullOriginalClassName;
        } else {
            $this->namespace = substr($fullOriginalClassName, 0, strrpos($fullOriginalClassName, '\\'));
            $this->originalClassName = substr($fullOriginalClassName, strlen($this->namespace) + 1);
        }
        $this->fullOriginalClassName = $fullOriginalClassName;
    }

    /**
     * Injects the Reflection Service
     *
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Returns the ProxyConstructor for this ProxyClass. Creates it if needed.
     *
     * @return ProxyConstructor
     */
    public function getConstructor(): ProxyConstructor
    {
        if (!isset($this->constructor)) {
            $this->constructor = new ProxyConstructor($this->fullOriginalClassName);
            $this->constructor->injectReflectionService($this->reflectionService);
        }
        return $this->constructor;
    }

    /**
     * Returns the named ProxyMethod for this ProxyClass. Creates it if needed.
     *
     * @param string $methodName The name of the methods to return
     * @return ProxyMethod
     */
    public function getMethod(string $methodName): ProxyMethod
    {
        if ($methodName === '__construct') {
            return $this->getConstructor();
        }
        if (!isset($this->methods[$methodName])) {
            $this->methods[$methodName] = new ProxyMethod($this->fullOriginalClassName, $methodName);
            $this->methods[$methodName]->injectReflectionService($this->reflectionService);
        }
        return $this->methods[$methodName];
    }

    /**
     * Adds a constant to this proxy class
     *
     * @param string $name Name of the constant. Should be ALL_UPPERCASE_WITH_UNDERSCORES
     * @param string $valueCode PHP code which assigns the value. Example: 'foo' (including quotes!)
     * @return void
     */
    public function addConstant(string $name, string $valueCode)
    {
        $this->constants[$name] = $valueCode;
    }

    /**
     * Adds a class property to this proxy class
     *
     * @param string $name Name of the property
     * @param string $initialValueCode PHP code of the initial value assignment
     * @param string $visibility
     * @param string $docComment
     * @return void
     */
    public function addProperty(string $name, string $initialValueCode, string $visibility = 'private', string $docComment = '')
    {
        // TODO: Add support for PHP attributes?
        $this->properties[$name] = [
            'initialValueCode' => $initialValueCode,
            'visibility' => $visibility,
            'docComment' => $docComment
        ];
    }

    /**
     * Adds one or more interfaces to the "implements" section of the class definition.
     *
     * Note that the passed interface names must already have a leading backslash,
     * for example "\Neos\Flow\Foo\BarInterface".
     *
     * @param array $interfaceNames Fully qualified names of the interfaces to introduce
     * @return void
     */
    public function addInterfaces(array $interfaceNames)
    {
        $this->interfaces = array_merge($this->interfaces, $interfaceNames);
    }

    /**
     * Adds one or more traits to the class definition.
     *
     * Note that the passed trait names must have a leading backslash,
     * for example "\Neos\Flow\ObjectManagement\Proxy\PropertyInjectionTrait".
     *
     * @param array $traitNames
     * @return void
     */
    public function addTraits(array $traitNames)
    {
        $this->traits = array_merge($this->traits, $traitNames);
    }

    /**
     * Renders and returns the PHP code for this ProxyClass.
     *
     * @return string
     * @throws ReflectionException
     * @throws ClassLoadingForReflectionFailedException
     * @throws CannotBuildObjectException
     */
    public function render(): string
    {
        $proxyClassName = $this->originalClassName;
        $originalClassName = $this->originalClassName . Compiler::ORIGINAL_CLASSNAME_SUFFIX;
        if ($this->reflectionService->isClassAbstract($this->fullOriginalClassName)) {
            $classModifier = 'abstract ';
        } else {
            # Don't add a "final" class modified, even if the original class had one, because
            # otherwise, a Lazy Proxy cannot extend the generated proxy class.
            $classModifier = '';
        }

        $constantsCode = $this->renderConstantsCode();
        $propertiesCode = $this->renderPropertiesCode();
        $traitsCode = $this->renderTraitsCode();

        $methodsCode = isset($this->constructor) ? $this->constructor->render() : '';
        foreach ($this->methods as $proxyMethod) {
            $methodsCode .= $proxyMethod->render();
        }

        if ($methodsCode . $constantsCode === '') {
            return '';
        }
        $classCode = $this->buildClassDocumentation() .
            $classModifier . 'class ' . $proxyClassName . ' extends ' . $originalClassName . ' implements ' . implode(', ', array_unique($this->interfaces)) . " {\n\n" .
            $traitsCode .
            $constantsCode .
            $propertiesCode .
            $methodsCode .
            "}\n\n";

        $hasInterfaceWithConstructor = false;
        foreach ((new ClassReflection($this->fullOriginalClassName))->getInterfaceNames() as $interfaceName) {
            if (method_exists($interfaceName, '__construct')) {
                $hasInterfaceWithConstructor = true;
                break;
            }
        }

        $hasFinalMethod = false;
        foreach (get_class_methods($this->fullOriginalClassName) as $methodName) {
            if ($this->reflectionService->isMethodFinal($this->fullOriginalClassName, $methodName)) {
                $hasFinalMethod = true;
                break;
            }
        }

        if (!$hasInterfaceWithConstructor && !$hasFinalMethod && !$this->reflectionService->isClassAbstract($this->fullOriginalClassName)) {
            $classCode .= $this->buildLazyProxyClass($proxyClassName);
        }

        return $classCode;
    }

    /**
     * Builds the class documentation block for the specified class keeping doc comments and vital annotations
     *
     * @return string $methodDocumentation DocComment for the given method
     * @throws ClassLoadingForReflectionFailedException
     */
    protected function buildClassDocumentation(): string
    {
        $classReflection = new ClassReflection($this->fullOriginalClassName);

        $classDocumentation = str_replace("*/", "* @codeCoverageIgnore\n */", $classReflection->getDocComment()) . "\n";
        if (PHP_MAJOR_VERSION >= 8) {
            foreach ($classReflection->getAttributes() as $attribute) {
                $classDocumentation .= Compiler::renderAttribute($attribute) . "\n";
            }
        }

        return $classDocumentation;
    }

    /**
     * Renders code for the added class constants
     *
     * @return string
     */
    protected function renderConstantsCode(): string
    {
        $code = '';
        foreach ($this->constants as $name => $valueCode) {
            $code .= '    const ' . $name . ' = ' . $valueCode . ";\n\n";
        }
        return $code;
    }

    /**
     * Renders code for the added class properties
     *
     * @return string
     */
    protected function renderPropertiesCode(): string
    {
        $code = '';
        foreach ($this->properties as $name => $attributes) {
            if (!empty($attributes['docComment'])) {
                $code .= '    ' . $attributes['docComment'] . "\n";
            }
            $code .= '    ' . $attributes['visibility'] . ' $' . $name . ' = ' . $attributes['initialValueCode'] . ";\n\n";
        }
        return $code;
    }

    /**
     * Renders code for added traits
     *
     * @return string
     */
    protected function renderTraitsCode(): string
    {
        if ($this->traits === []) {
            return '';
        }

        return '    use ' . implode(', ', $this->traits) . ";\n\n";
    }

    /**
     * @param $proxyClassName
     * @return string
     * @throws ReflectionException
     */
    protected function buildLazyProxyClass($proxyClassName): string
    {
        $methods = [$this->buildCallMagicMethod()];

        foreach (get_class_methods($this->fullOriginalClassName) as $methodName) {
            if (!$this->reflectionService->isMethodPublic($this->fullOriginalClassName, $methodName) || $methodName === '__call' || $methodName === '__construct') {
                continue;
            }
            $methodReturnType = $this->reflectionService->getMethodDeclaredReturnType($this->fullOriginalClassName, $methodName);
            $returnKeyword = ($methodReturnType === 'void') ? '' : 'return ';
            $method = MethodGenerator::fromReflection(new \Laminas\Code\Reflection\MethodReflection($this->fullOriginalClassName, $methodName));
            $method->removeDocBlock();
            $method->setBody(
                <<< CODE
                    \$arguments = func_get_args();
                    {$returnKeyword}\$this->_activateDependency()->{$methodName}(...\$arguments);
                CODE
            );
            $methods[] = $method;
        }

        $classGenerator = ClassGenerator::fromArray(['name' => $proxyClassName . '_LazyProxy'])
            ->setExtendedClass($proxyClassName)
            ->setImplementedInterfaces([DependencyProxy::class])
            ->removeMethod('__call')
            ->addMethods($methods)
            ->addTrait('\\' . DependencyProxyTrait::class);
        // This is just for making Psalm happy, since addTrait() returns TraitUsageInterface which doesn't provide generate()
        assert($classGenerator instanceof ClassGenerator);
        return $classGenerator->generate();
    }

    /**
     * @return MethodGenerator
     * @throws ReflectionException
     */
    protected function buildCallMagicMethod(): MethodGenerator
    {
        if (method_exists($this->fullOriginalClassName, '__call')) {
            $callMagicMethod = MethodGenerator::fromReflection(new \Laminas\Code\Reflection\MethodReflection($this->fullOriginalClassName, '__call'));
        } else {
            $callMagicMethod = MethodGenerator::fromArray(['name' => '__call']);
            $callMagicMethod->setParameters([
                ParameterGenerator::fromArray(['name' => 'methodName', 'type' => 'string']),
                ParameterGenerator::fromArray(['name' => 'arguments', 'type' => 'array']),
            ]);
        }

        $callMagicMethod->setBody(
            <<< CODE
                [\$methodName, \$arguments] = func_get_args();
                return \$this->_activateDependency()->\$methodName(...\$arguments);
            CODE
        );
        $callMagicMethod->removeDocBlock();
        return $callMagicMethod;
    }
}
