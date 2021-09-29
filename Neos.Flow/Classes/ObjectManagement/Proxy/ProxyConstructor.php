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

use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;

/**
 * Representation of a constructor method within a proxy class
 *
 */
class ProxyConstructor extends ProxyMethod
{
    /**
     *
     *
     * @param string $fullOriginalClassName The fully qualified class name of the original class
     * @psalm-param class-string $fullOriginalClassName
     */
    public function __construct($fullOriginalClassName)
    {
        parent::__construct($fullOriginalClassName, '__construct');
    }

    /**
     * Renders the code for a proxy constructor
     *
     * @return string PHP code
     * @throws CannotBuildObjectException|ClassLoadingForReflectionFailedException
     */
    public function render()
    {
        $methodDocumentation = $this->buildMethodDocumentation($this->fullOriginalClassName, $this->methodName);
        $callParentMethodCode = $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->methodName);

        $finalKeyword = $this->reflectionService->isMethodFinal($this->fullOriginalClassName, $this->methodName) ? 'final ' : '';
        $staticKeyword = $this->reflectionService->isMethodStatic($this->fullOriginalClassName, $this->methodName) ? 'static ' : '';

        $code = '';
        if ($this->addedPreParentCallCode !== '' || $this->addedPostParentCallCode !== '') {
            foreach ((new ClassReflection($this->fullOriginalClassName))->getInterfaceNames() as $interfaceName) {
                if ($this->reflectionService->getMethodParameters($interfaceName, $this->methodName) !== []) {
                    throw new CannotBuildObjectException(sprintf('Cannot build proxy for class "%s" because it implements interface "%s" that defines a constructor with arguments. Adjust the interface or avoid the proxy class from being created (for example by adjusting the Method privilege in your Policy)', $this->fullOriginalClassName, $interfaceName), 1630656424);
                }
            }
            $argumentsCode = (count($this->reflectionService->getMethodParameters($this->fullOriginalClassName, $this->methodName)) > 0) ? '        $arguments = func_get_args();' . "\n" : '';
            $code = "\n" .
                $methodDocumentation .
                '    ' . $finalKeyword . $staticKeyword . "public function __construct()\n    {\n" .
                $argumentsCode .
                $this->addedPreParentCallCode . $callParentMethodCode . $this->addedPostParentCallCode .
                "    }\n";
        }
        return $code;
    }

    /**
     * Builds PHP code which calls the original (ie. parent) method after the added code has been executed.
     *
     * @param string $fullClassName Fully qualified name of the original class
     * @param string $methodName Name of the original method
     * @return string PHP code
     */
    protected function buildCallParentMethodCode($fullClassName, $methodName)
    {
        if (!$this->reflectionService->hasMethod($fullClassName, $methodName)) {
            return '';
        }
        if (count($this->reflectionService->getMethodParameters($this->fullOriginalClassName, $this->methodName)) > 0) {
            return "        parent::" . $methodName . "(...\$arguments);\n";
        } else {
            return "        parent::" . $methodName . "();\n";
        }
    }
}
