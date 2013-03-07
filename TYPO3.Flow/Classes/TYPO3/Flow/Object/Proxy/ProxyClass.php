<?php
namespace TYPO3\Flow\Object\Proxy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cache\CacheManager;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Representation of a Proxy Class during rendering time
 *
 * @Flow\Proxy(false)
 */
class ProxyClass {

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
	 */
	protected $fullOriginalClassName;

	/**
	 * @var \TYPO3\Flow\Object\Proxy\ProxyConstructor
	 */
	protected $constructor;

	/**
	 * @var array
	 */
	protected $methods = array();

	/**
	 * @var array
	 */
	protected $constants = array();

	/**
	 * @var array
	 */
	protected $interfaces = array('\TYPO3\Flow\Object\Proxy\ProxyInterface');

	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Creates a new ProxyClass instance.
	 *
	 * @param string $fullOriginalClassName The fully qualified class name of the original class
	 */
	public function __construct($fullOriginalClassName) {
		if (strpos($fullOriginalClassName, '\\') === FALSE) {
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
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Returns the ProxyConstructor for this ProxyClass. Creates it if needed.
	 *
	 * @return \TYPO3\Flow\Object\Proxy\ProxyConstructor
	 */
	public function getConstructor() {
		if (!isset($this->constructor)) {
			$this->constructor = new \TYPO3\Flow\Object\Proxy\ProxyConstructor($this->fullOriginalClassName);
			$this->constructor->injectReflectionService($this->reflectionService);
		}
		return $this->constructor;
	}

	/**
	 * Returns the named ProxyMethod for this ProxyClass. Creates it if needed.
	 *
	 * @param string $methodName The name of the methods to return
	 * @return \TYPO3\Flow\Object\Proxy\ProxyMethod
	 */
	public function getMethod($methodName) {
		if ($methodName === '__construct') {
			return $this->getConstructor();
		}
		if (!isset($this->methods[$methodName])) {
			$this->methods[$methodName] = new \TYPO3\Flow\Object\Proxy\ProxyMethod($this->fullOriginalClassName, $methodName);
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
	public function addConstant($name, $valueCode) {
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
	public function addProperty($name, $initialValueCode, $visibility = 'private', $docComment = '') {
		$this->properties[$name] = array(
			'initialValueCode' => $initialValueCode,
			'visibility' => $visibility,
			'docComment' => $docComment
		);
	}

	/**
	 * Adds one or more interfaces to the "implements" section of the class definition.
	 *
	 * Note that the passed interface names must already have a leading backslash,
	 * for example "\TYPO3\Flow\Foo\BarInterface".
	 *
	 * @param array $interfaceNames Fully qualified names of the interfaces to introduce
	 * @return void
	 */
	public function addInterfaces(array $interfaceNames) {
		$this->interfaces = array_merge($this->interfaces, $interfaceNames);
	}

	/**
	 * Renders and returns the PHP code for this ProxyClass.
	 *
	 * @return string
	 */
	public function render() {
		$namespace = $this->namespace;
		$proxyClassName = $this->originalClassName;
		$originalClassName = $this->originalClassName . \TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX;
		$abstractKeyword = $this->reflectionService->isClassAbstract($this->fullOriginalClassName) ? 'abstract ' : '';

		$constantsCode = $this->renderConstantsCode();
		$propertiesCode = $this->renderPropertiesCode();

		$methodsCode = isset($this->constructor) ? $this->constructor->render() : '';
		foreach ($this->methods as $proxyMethod) {
			$methodsCode .= $proxyMethod->render();
		}

		if ($methodsCode . $constantsCode === '') {
			return '';
		}
		$classCode =
			"namespace $namespace;\n" .
			"\n" .
			"use Doctrine\\ORM\\Mapping as ORM;\n" .
			"use TYPO3\\Flow\\Annotations as Flow;\n" .
			"\n" .
			$this->buildClassDocumentation() .
			$abstractKeyword . "class $proxyClassName extends $originalClassName implements " . implode(', ', array_unique($this->interfaces)) ." {\n\n" .
			$constantsCode .
			$propertiesCode .
			$methodsCode .
			"}";
		return $classCode;
	}

	/**
	 * Builds the class documentation block for the specified class keeping doc comments and vital annotations
	 *
	 * @return string $methodDocumentation DocComment for the given method
	 */
	protected function buildClassDocumentation() {
		$classDocumentation = "/**\n";

		$classReflection = new \TYPO3\Flow\Reflection\ClassReflection($this->fullOriginalClassName);
		$classDescription = $classReflection->getDescription();
		$classDocumentation .= ' * ' . str_replace("\n", "\n * ", $classDescription) . "\n";

		foreach ($this->reflectionService->getClassAnnotations($this->fullOriginalClassName) as $annotation) {
			$classDocumentation .= ' * ' . \TYPO3\Flow\Object\Proxy\Compiler::renderAnnotation($annotation) . "\n";
		}

		$classDocumentation .= " */\n";
		return $classDocumentation;
	}

	/**
	 * Renders code for the added class constants
	 *
	 * @return string
	 */
	protected function renderConstantsCode() {
		$code = '';
		foreach ($this->constants as $name => $valueCode) {
			$code .= '	const ' . $name . ' = ' . $valueCode . ";\n\n";
		}
		return $code;
	}

	/**
	 * Renders code for the added class properties
	 *
	 * @return string
	 */
	protected function renderPropertiesCode() {
		$code = '';
		foreach ($this->properties as $name => $attributes) {
			if (!empty($attributes['docComment'])) {
				$code .= '	' . $attributes['docComment'] . "\n";
			}
			$code .= '	' . $attributes['visibility'] . ' $' . $name . ' = ' . $attributes['initialValueCode'] . ";\n\n";
		}
		return $code;
	}
}
?>