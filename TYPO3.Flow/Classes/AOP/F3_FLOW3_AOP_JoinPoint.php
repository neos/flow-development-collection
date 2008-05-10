<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 */

/**
 * In FLOW3 the join point object contains context information when a point cut
 * matches and the registered advices are executed.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_JoinPoint.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_JoinPoint implements F3_FLOW3_AOP_JoinPointInterface {

	/**
	 * @var F3_FLOW3_AOP_ProxyInterface A reference to the proxy object
	 */
	protected $proxy;

	/**
	 * @var string Class name of the target class this join point refers to
	 */
	protected $className;

	/**
	 * @var string Method name of the target method which is about to or has been invoked
	 */
	protected $methodName;

	/**
	 * @var array Array of method arguments which have been passed to the target method
	 */
	protected $methodArguments;

	/**
	 * @var F3_FLOW3_AOP_AdviceChainInterface The advice chain for this join point
	 */
	protected $adviceChain;

	/**
	 * @var mixed The result of the method invocations (only used for After Returning advices)
	 */
	protected $result = NULL;

	/**
	 * @var Exception The exception thrown (only used for After Throwing advices)
	 */
	protected $exception = NULL;

	/**
	 * Constructor, creates the join point
	 *
	 * @param F3_FLOW3_AOP_ProxyInterface $proxy: Reference to the proxy class instance of the target class
	 * @param string $className: Class name of the target class this join point refers to
	 * @param string $methodName: Method name of the target method which is about to or has been invoked
	 * @param array $methodArguments: Array of method arguments which have been passed to the target method
	 * @param F3_FLOW3_AOP_AdviceChainInterface $adviceChain: The advice chain for this join point
	 * @param mixed $result: The result of the method invocations (only used for After Returning advices)
	 * @param Exception $exception: The exception thrown (only used for After Throwing advices)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_AOP_ProxyInterface $proxy, $className, $methodName, $methodArguments, $adviceChain = NULL, $result = NULL, $exception = NULL) {
		if ($adviceChain !== NULL && !$adviceChain instanceof F3_FLOW3_AOP_AdviceChain) throw new InvalidArgumentException('The advice chain must be an instance of F3_FLOW3_AOP_AdviceChain.', 1171482537);

		$this->proxy = $proxy;
		$this->className = $className;
		$this->methodName = $methodName;
		$this->methodArguments = $methodArguments;
		$this->adviceChain = $adviceChain;
		$this->result = $result;
		$this->exception = $exception;
	}

	/**
	 * Returns the reference to the proxy class instance
	 *
	 * @return F3_FLOW3_AOP_ProxyInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProxy() {
		return $this->proxy;
	}

	/**
	 * Returns the class name of the target class this join point refers to
	 *
	 * @return string The class name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Returns the method name of the method this join point refers to
	 *
	 * @return string The method name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodName() {
		return $this->methodName;
	}

	/**
	 * Returns an array of arguments which have been passed to the target method
	 *
	 * @return array Array of arguments
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodArguments() {
		return $this->methodArguments;
	}

	/**
	 * Returns the value of the specified method argument
	 *
	 * @param  string $argumentName: Name of the argument
	 * @return mixed Value of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodArgument($argumentName) {
		if (!isset($this->methodArguments[$argumentName])) throw new RuntimeException('The argument "' . $argumentName . '" does not exist in method ' . $this->className . '->' . $this->methodName, 1172750905);
		return $this->methodArguments[$argumentName];
	}

	/**
	 * Returns TRUE if the argument with the specified name exists in the
	 * method call this joinpoint refers to.
	 *
	 * @param  string $argumentName: Name of the argument to check
	 * @return boolean TRUE if the argument exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isMethodArgument($argumentName) {
		return isset($this->methodArguments[$argumentName]);
	}

	/**
	 * Returns the advice chain related to this join point
	 *
	 * @return F3_FLOW3_AOP_AdviceChainInterface The advice chain
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvicechain() {
		return $this->adviceChain;
	}

	/**
	 * Returns the exception which has been thrown in the target method.
	 * If no exception has been thrown, NULL is returned.
	 * Only makes sense for After Throwing advices.
	 *
	 * @return mixed The exception thrown or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getException() {
		return $this->exception;
	}

	/**
	 * Returns the result of the method invocation. The result is only
	 * available for afterReturning advices.
	 *
	 * @return mixed Result of the method invocation
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getResult() {
		return $this->result;
	}
}

?>