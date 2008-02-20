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
 * Contract for a join point
 * 
 * @package		FLOW3
 * @subpackage	AOP
 * @version 	$Id:T3_FLOW3_AOP_JoinPointInterface.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @author		Robert Lemke <robert@typo3.org>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_AOP_JoinPointInterface {

	/**
	 * Constructor, creates the join point 
	 *
	 * @param  T3_FLOW3_AOP_ProxyInterface $proxy: Reference to the proxy class instance of the target class
	 * @param  string			$className: Class name of the target class this join point refers to
	 * @param  string			$methodName: Method name of the target method which is about to or has been invoked
	 * @param  array			$methodArguments: Array of method arguments which have been passed to the target method
	 * @param  T3_FLOW3_AOP_AdviceChainInterface $adviceChain: The advice chain for this join point
	 * @param  mixed			$result: The result of the method invocations (only used for After Returning advices)
	 * @param  object			$exception: The exception thrown (only used for After Throwing advices)
	 * @return void
	 */
	public function __construct(T3_FLOW3_AOP_ProxyInterface $proxy, $className, $methodName, $methodArguments, $adviceChain = NULL, $result = NULL, $exception = NULL);

	/**
	 * Returns the reference to the proxy class instance
	 *
	 * @return T3_FLOW3_AOP_ProxyInterface
	 */
	public function getProxy();
	
	/**
	 * Returns the class name of the target class this join point refers to
	 *
	 * @return string	The class name
	 */
	public function getClassName();

	/**
	 * Returns the method name of the method this join point refers to
	 *
	 * @return string	The method name
	 */
	public function getMethodName();
	
	/**
	 * Returns an array of arguments which have been passed to the target method
	 *
	 * @return array	Array of arguments
	 */
	public function getMethodArguments();
	
	/**
	 * Returns the value of the specified method argument
	 *
	 * @param  string	$argumentName: Name of the argument
	 * @return mixed	Value of the argument
	 */
	public function getMethodArgument($argumentName);
	
	/**
	 * Returns TRUE if the argument with the specified name exists in the
	 * method call this joinpoint refers to.
	 *
	 * @param  string	$argumentName: Name of the argument to check
	 * @return boolean	TRUE if the argument exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isMethodArgument($argumentName);
	
	/**
	 * Returns the advice chain related to this join point
	 *
	 * @return T3_FLOW3_AOP_AdviceChainInterface The advice chain
	 */
	public function getAdvicechain();
	
	/**
	 * Returns the exception which has been thrown in the target method.
	 * If no exception has been thrown, NULL is returned.
	 * Only makes sense for After Throwing advices.
	 *
	 * @return object		The exception thrown or NULL
	 */
	public function getException();

	/**
	 * Returns the result of the method invocation. The result is only
	 * available for afterReturning advices.
	 *
	 * @return mixed	Result of the method invocation
	 */
	public function getResult();
	
}

?>