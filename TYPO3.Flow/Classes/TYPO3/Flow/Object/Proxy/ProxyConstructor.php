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

/**
 * Representation of a constructor method within a proxy class
 *
 */
class ProxyConstructor extends \TYPO3\Flow\Object\Proxy\ProxyMethod {

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
	public function __construct($fullOriginalClassName) {
		$this->fullOriginalClassName = $fullOriginalClassName;
	}

	/**
	 * Renders the code for a proxy constructor
	 *
	 * @return string PHP code
	 */
	public function render() {
		$methodDocumentation = $this->buildMethodDocumentation($this->fullOriginalClassName, $this->methodName);
		$callParentMethodCode = $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->methodName);

		$staticKeyword = $this->reflectionService->isMethodStatic($this->fullOriginalClassName, $this->methodName) ? 'static ' : '';

		$code = '';
		if ($this->addedPreParentCallCode !== '' || $this->addedPostParentCallCode !== '') {
			$argumentsCode = (count($this->reflectionService->getMethodParameters($this->fullOriginalClassName, $this->methodName)) > 0) ? '		$arguments = func_get_args();' . "\n" : '';
			$code = "\n" .
				$methodDocumentation .
				"	" . $staticKeyword . "public function __construct() {\n" .
				$argumentsCode .
				$this->addedPreParentCallCode . $callParentMethodCode . $this->addedPostParentCallCode .
				"	}\n";
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
	protected function buildCallParentMethodCode($fullClassName, $methodName) {
		if (!$this->reflectionService->hasMethod($fullClassName, $methodName)) {
			return '';
		}
		if (count($this->reflectionService->getMethodParameters($this->fullOriginalClassName, $this->methodName)) > 0) {
			return "\t\tcall_user_func_array('parent::" . $methodName . "', \$arguments);\n";
		} else {
			return "\t\tparent::" . $methodName . "();\n";
		}
	}

}
