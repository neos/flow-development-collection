<?php
namespace TYPO3\FLOW3\Aop;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * In FLOW3 the join point object contains context information when a point cut
 * matches and the registered advices are executed.
 *
 * @api
 */
class JoinPoint implements \TYPO3\FLOW3\Aop\JoinPointInterface {

	/**
	 * A reference to the proxy object
	 * @var \TYPO3\FLOW3\Object\Proxy\ProxyInterface
	 */
	protected $proxy;

	/**
	 * Class name of the target class this join point refers to
	 * @var string
	 */
	protected $className;

	/**
	 * Method name of the target method which is about to or has been invoked
	 * @var string
	 */
	protected $methodName;

	/**
	 * Array of method arguments which have been passed to the target method
	 * @var array
	 */
	protected $methodArguments;

	/**
	 * The advice chain for this join point
	 * @var \TYPO3\FLOW3\Aop\Advice\AdviceChain
	 */
	protected $adviceChain;

	/**
	 * The result of the method invocations (only used for After Returning advices)
	 * @var mixed
	 */
	protected $result = NULL;

	/**
	 * The exception thrown (only used for After Throwing advices)
	 * @var \Exception
	 */
	protected $exception = NULL;

	/**
	 * Constructor, creates the join point
	 *
	 * @param object $proxy Reference to the proxy class instance of the target class
	 * @param string $className Class name of the target class this join point refers to
	 * @param string $methodName Method name of the target method which is about to or has been invoked
	 * @param array $methodArguments Array of method arguments which have been passed to the target method
	 * @param \TYPO3\FLOW3\Aop\Advice\AdviceChain $adviceChain The advice chain for this join point
	 * @param mixed $result The result of the method invocations (only used for After Returning advices)
	 * @param Exception $exception The exception thrown (only used for After Throwing advices)
	 * @return void
	 */
	public function __construct($proxy, $className, $methodName, $methodArguments, $adviceChain = NULL, $result = NULL, $exception = NULL) {
		if ($adviceChain !== NULL && !$adviceChain instanceof \TYPO3\FLOW3\Aop\Advice\AdviceChain) throw new \InvalidArgumentException('The advice chain must be an instance of \TYPO3\FLOW3\Aop\Advice\AdviceChain.', 1171482537);

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
	 * @return \TYPO3\FLOW3\Object\Proxy\ProxyInterface
	 * @api
	 */
	public function getProxy() {
		return $this->proxy;
	}

	/**
	 * Returns the class name of the target class this join point refers to
	 *
	 * @return string The class name
	 * @api
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Returns the method name of the method this join point refers to
	 *
	 * @return string The method name
	 * @api
	 */
	public function getMethodName() {
		return $this->methodName;
	}

	/**
	 * Returns an array of arguments which have been passed to the target method
	 *
	 * @return array Array of arguments
	 * @api
	 */
	public function getMethodArguments() {
		return $this->methodArguments;
	}

	/**
	 * Returns the value of the specified method argument
	 *
	 * @param  string $argumentName Name of the argument
	 * @return mixed Value of the argument
	 * @api
	 */
	public function getMethodArgument($argumentName) {
		if (!array_key_exists($argumentName, $this->methodArguments)) throw new \TYPO3\FLOW3\Aop\Exception\InvalidArgumentException('The argument "' . $argumentName . '" does not exist in method ' . $this->className . '->' . $this->methodName, 1172750905);
		return $this->methodArguments[$argumentName];
	}

	/**
	 * Sets the value of the specified method argument
	 *
	 * @param string $argumentName Name of the argument
	 * @param mixed $argumentValue Value of the argument
	 * @return void
	 * @api
	 */
	public function setMethodArgument($argumentName, $argumentValue) {
		if (!array_key_exists($argumentName, $this->methodArguments)) throw new \TYPO3\FLOW3\Aop\Exception\InvalidArgumentException('The argument "' . $argumentName . '" does not exist in method ' . $this->className . '->' . $this->methodName, 1309260269);
		$this->methodArguments[$argumentName] = $argumentValue;
	}

	/**
	 * Returns TRUE if the argument with the specified name exists in the
	 * method call this joinpoint refers to.
	 *
	 * @param  string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument exists
	 * @api
	 */
	public function isMethodArgument($argumentName) {
		return isset($this->methodArguments[$argumentName]);
	}

	/**
	 * Returns the advice chain related to this join point
	 *
	 * @return \TYPO3\FLOW3\Aop\Advice\AdviceChain The advice chain
	 * @api
	 */
	public function getAdviceChain() {
		return $this->adviceChain;
	}

	/**
	 * If an exception was thrown by the target method
	 * Only makes sense for After Throwing advices.
	 *
	 * @return boolean
	 * @api
	 */
	public function hasException() {
		return $this->exception !== NULL;
	}

	/**
	 * Returns the exception which has been thrown in the target method.
	 * If no exception has been thrown, NULL is returned.
	 * Only makes sense for After Throwing advices.
	 *
	 * @return mixed The exception thrown or NULL
	 * @api
	 */
	public function getException() {
		return $this->exception;
	}

	/**
	 * Returns the result of the method invocation. The result is only
	 * available for AfterReturning advices.
	 *
	 * @return mixed Result of the method invocation
	 * @api
	 */
	public function getResult() {
		return $this->result;
	}
}

?>