<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Proxy;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Representation of a constructor method within a proxy class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ProxyConstructor extends \F3\FLOW3\Object\Proxy\ProxyMethod {

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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($fullOriginalClassName) {
		$this->fullOriginalClassName = $fullOriginalClassName;
	}

	/**
	 * Renders the code for a proxy constructor
	 *
	 * @return string PHP code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$methodDocumentation = $this->buildMethodDocumentation($this->fullOriginalClassName, $this->methodName);
		$callParentMethodCode = $this->buildCallParentMethodCode($this->fullOriginalClassName, $this->methodName);

		$staticKeyword = $this->reflectionService->isMethodStatic($this->fullOriginalClassName, $this->methodName) ? 'static ' : '';

		$code = '';
		if (strlen($this->addedPreParentCallCode) + strlen($this->addedPostParentCallCode) > 0) {
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
	 * @author Robert Lemke <robert@typo3.org>
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
?>