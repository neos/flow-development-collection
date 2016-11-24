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

/**
 * Representation of a constructor method within a proxy class
 *
 */
class ProxyConstructor extends ProxyMethod
{
    /**
     * Name of the original method
     *
     * @var string
     */
    protected $methodName = '__construct';

    /**
     *
     *
     * @param string $fullOriginalClassName The fully qualified class name of the original class
     */
    public function __construct($fullOriginalClassName)
    {
        $this->fullOriginalClassName = $fullOriginalClassName;
    }

    /**
     * Renders the code for a proxy constructor
     *
     * @return string PHP code
     */
    public function render()
    {
        $methodDocumentation = $this->buildMethodDocumentation($this->fullOriginalClassName, $this->methodName);
        $callParentMethodCode = $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->methodName);

        $staticKeyword = $this->reflectionService->isMethodStatic($this->fullOriginalClassName, $this->methodName) ? 'static ' : '';

        $code = '';
        if ($this->addedPreParentCallCode !== '' || $this->addedPostParentCallCode !== '') {
            $argumentsCode = (count($this->reflectionService->getMethodParameters($this->fullOriginalClassName, $this->methodName)) > 0) ? '        $arguments = func_get_args();' . "\n" : '';
            $code = "\n" .
                $methodDocumentation .
                '    ' . $staticKeyword . "public function __construct()\n    {\n" .
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
            return "        call_user_func_array('parent::" . $methodName . "', \$arguments);\n";
        } else {
            return "        parent::" . $methodName . "();\n";
        }
    }
}
