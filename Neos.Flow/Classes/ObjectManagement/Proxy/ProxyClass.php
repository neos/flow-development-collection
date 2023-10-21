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

use Laminas\Code\Reflection\MethodReflection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\ReflectionService;

/**
 * Representation of a Proxy Class during rendering time
 */
#[Flow\Proxy(false)]
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
     * @var ProxyConstructorGenerator
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
     * @psalm-param class-string $fullOriginalClassName
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
    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Returns the ProxyConstructorGenerator for this ProxyClass. Creates it if needed.
     *
     * @return ProxyConstructorGenerator
     * @throws \ReflectionException
     * @throws CannotBuildObjectException
     */
    public function getConstructor(): ProxyConstructorGenerator
    {
        if (!isset($this->constructor)) {
            if (method_exists($this->fullOriginalClassName, '__construct')) {
                $this->constructor = ProxyConstructorGenerator::fromReflection(new MethodReflection($this->fullOriginalClassName, '__construct'));
            } else {
                $this->constructor = new ProxyConstructorGenerator();
                $this->constructor->setFullOriginalClassName($this->fullOriginalClassName);
            }
        }
        return $this->constructor;
    }

    /**
     * Returns the named ProxyMethod for this ProxyClass. Creates it if needed.
     *
     * @throws \ReflectionException
     */
    public function getMethod(string $methodName): ProxyMethodGenerator|ProxyConstructorGenerator
    {
        if ($methodName === '__construct') {
            return $this->getConstructor();
        }
        if (!isset($this->methods[$methodName])) {
            if (method_exists($this->fullOriginalClassName, $methodName)) {
                $this->methods[$methodName] = ProxyMethodGenerator::copyMethodSignatureAndDocblock(new MethodReflection($this->fullOriginalClassName, $methodName));
            } else {
                $this->methods[$methodName] = new ProxyMethodGenerator($methodName);
            }
        }
        $this->methods[$methodName]->getDocBlock()?->setWordWrap(false);
        return $this->methods[$methodName];
    }

    /**
     * Adds a constant to this proxy class
     *
     * @param string $name Name of the constant. Should be ALL_UPPERCASE_WITH_UNDERSCORES
     * @param string $valueCode PHP code which assigns the value. Example: 'foo' (including quotes!)
     * @return void
     */
    public function addConstant(string $name, string $valueCode): void
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
    public function addProperty(string $name, string $initialValueCode, string $visibility = 'private', string $docComment = ''): void
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
    public function addInterfaces(array $interfaceNames): void
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
    public function addTraits(array $traitNames): void
    {
        $this->traits = array_merge($this->traits, $traitNames);
    }

    /**
     * Renders and returns the PHP code for this ProxyClass.
     *
     * @return string
     * @throws CannotBuildObjectException
     */
    public function render(): string
    {
        $proxyClassName = $this->originalClassName;
        $originalClassName = $this->originalClassName . Compiler::ORIGINAL_CLASSNAME_SUFFIX;
        $classModifier = '';
        if ($this->reflectionService->isClassAbstract($this->fullOriginalClassName)) {
            $classModifier = 'abstract ';
        } elseif ($this->reflectionService->isClassFinal($this->fullOriginalClassName)) {
            $classModifier = 'final ';
        }
        if ($this->reflectionService->isClassReadonly($this->fullOriginalClassName)) {
            $classModifier .= 'readonly ';
        }

        $constantsCode = $this->renderConstantsCode();
        $propertiesCode = $this->renderPropertiesCode();
        $traitsCode = $this->renderTraitsCode();
        $methodsCode = '';

        $constructorBodyCode = $this->constructor?->renderBodyCode() ?? '';
        if ($constructorBodyCode !== '') {
            assert($this->constructor !== null);
            $this->constructor->setBody($constructorBodyCode);
            $methodsCode .= PHP_EOL . $this->constructor->generate();

            foreach (class_implements($this->fullOriginalClassName) as $interface) {
                /** @psalm-suppress ArgumentTypeCoercion */
                if (method_exists($interface, '__construct')) {
                    throw new CannotBuildObjectException(sprintf('The class "%s" implements the interface "%s" which has a constructor. Proxy classes implementing an interface containing a constructor is not supported and constructors interfaces are generally strongly discouraged.', $this->fullOriginalClassName, $interface), 1685433328);
                }
            }
        }

        foreach ($this->methods as $proxyMethod) {
            assert($proxyMethod instanceof ProxyMethodGenerator);
            if ($proxyMethod->willBeRendered()) {
                $methodsCode .= PHP_EOL . $proxyMethod->generate();
            }
        }

        if (trim($methodsCode) . $constantsCode === '') {
            return '';
        }

        return $this->buildClassDocumentation() .
            $classModifier . 'class ' . $proxyClassName . ' extends ' . $originalClassName . ' implements ' . implode(', ', array_unique($this->interfaces)) . " {" . PHP_EOL . PHP_EOL .
            $traitsCode .
            $constantsCode .
            $propertiesCode .
            rtrim($methodsCode) .
            PHP_EOL. '}';
    }

    /**
     * Builds the class documentation block for the specified class keeping doc comments and vital annotations
     *
     * @return string $methodDocumentation DocComment for the given method
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
            $code .= '    const ' . $name . ' = ' . $valueCode . ";\n";
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
            $code .= '    ' . $attributes['visibility'] . ' $' . $name . ' = ' . $attributes['initialValueCode'] . ";\n";
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
}
