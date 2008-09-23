<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

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
 * Contract for a join point
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3::FLOW3::AOP::JoinPointInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface JoinPointInterface {

	/**
	 * Returns the reference to the proxy class instance
	 *
	 * @return F3::FLOW3::AOP::ProxyInterface
	 */
	public function getProxy();

	/**
	 * Returns the class name of the target class this join point refers to
	 *
	 * @return string The class name
	 */
	public function getClassName();

	/**
	 * Returns the method name of the method this join point refers to
	 *
	 * @return string The method name
	 */
	public function getMethodName();

	/**
	 * Returns an array of arguments which have been passed to the target method
	 *
	 * @return array Array of arguments
	 */
	public function getMethodArguments();

	/**
	 * Returns the value of the specified method argument
	 *
	 * @param  string $argumentName: Name of the argument
	 * @return mixed Value of the argument
	 */
	public function getMethodArgument($argumentName);

	/**
	 * Returns TRUE if the argument with the specified name exists in the
	 * method call this joinpoint refers to.
	 *
	 * @param string $argumentName: Name of the argument to check
	 * @return boolean TRUE if the argument exists
	 */
	public function isMethodArgument($argumentName);

	/**
	 * Returns the advice chain related to this join point
	 *
	 * @return F3::FLOW3::AOP::AdviceChainInterface The advice chain
	 */
	public function getAdviceChain();

	/**
	 * Returns the exception which has been thrown in the target method.
	 * If no exception has been thrown, NULL is returned.
	 * Only makes sense for After Throwing advices.
	 *
	 * @return object The exception thrown or NULL
	 */
	public function getException();

	/**
	 * Returns the result of the method invocation. The result is only
	 * available for afterReturning advices.
	 *
	 * @return mixed Result of the method invocation
	 */
	public function getResult();

}

?>