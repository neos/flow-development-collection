<?php
namespace TYPO3\FLOW3\Aop\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A filter which refers to another pointcut.
 *
 * @FLOW3\Proxy(false)
 */
class PointcutFilter implements \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface {

	/**
	 * Name of the aspect class where the pointcut was declared
	 * @var string
	 */
	protected $aspectClassName;

	/**
	 * Name of the pointcut method
	 * @var string
	 */
	protected $pointcutMethodName;

	/**
	 * The pointcut this filter is based on
	 * @var \TYPO3\FLOW3\Aop\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * A reference to the AOP Proxy ClassBuilder
	 * @var \TYPO3\FLOW3\Aop\Builder\ProxyClassBuilder
	 */
	protected $proxyClassBuilder;

	/**
	 * The constructor - initializes the pointcut filter with the name of the pointcut we're refering to
	 *
	 * @param string $aspectClassName Name of the aspect class containing the pointcut
	 * @param string $pointcutMethodName Name of the method which acts as an anchor for the pointcut name and expression
	 */
	public function __construct($aspectClassName, $pointcutMethodName) {
		$this->aspectClassName = $aspectClassName;
		$this->pointcutMethodName = $pointcutMethodName;
	}

	/**
	 * Injects the AOP Proxy Class Builder
	 *
	 * @param \TYPO3\FLOW3\Aop\Builder\ProxyClassBuilder $proxyClassBuilder
	 * @return void
	 */
	public function injectProxyClassBuilder(\TYPO3\FLOW3\Aop\Builder\ProxyClassBuilder $proxyClassBuilder) {
		$this->proxyClassBuilder = $proxyClassBuilder;
	}

	/**
	 * Checks if the specified class and method matches with the pointcut
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method - not used here
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @throws \TYPO3\FLOW3\Aop\Exception\UnknownPointcutException
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->pointcut === NULL) {
			$this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName);
		}
		if ($this->pointcut === FALSE) {
			throw new \TYPO3\FLOW3\Aop\Exception\UnknownPointcutException('No pointcut "' . $this->pointcutMethodName . '" found in aspect class "' . $this->aspectClassName . '" .', 1172223694);
		}
		return $this->pointcut->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return $this->pointcut->hasRuntimeEvaluationsDefinition();
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition() {
		if ($this->pointcut === NULL) {
			$this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName);
		}
		if ($this->pointcut === FALSE) {
			return array();
		}

		return $this->pointcut->getRuntimeEvaluationsDefinition();
	}

	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param \TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\FLOW3\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex) {
		if ($this->pointcut === NULL) {
			$this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName);
		}
		if ($this->pointcut === FALSE) {
			return $classNameIndex;
		}
		return $this->pointcut->reduceTargetClassNames($classNameIndex);
	}
}

?>