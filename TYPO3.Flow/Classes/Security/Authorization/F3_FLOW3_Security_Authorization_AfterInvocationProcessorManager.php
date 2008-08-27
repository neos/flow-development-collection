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
 * @subpackage Security
 * @version $Id:$
 */

/**
 * The default after invocation manager that uses AfterInvocationProcessorInterface to process the return objects.
 * It resolves automatically any available AfterInvcocationProcessorInterface for the given return object and calls them.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_AfterInvocationProcessorManager implements F3_FLOW3_Security_Authorization_AfterInvocationManagerInterface {

	/**
	 * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
	 * It resolves any available AfterInvocationProcessor for the given return object and invokes them.
	 * The naming convention is: [InterceptedClassName]_[InterceptedMethodName]_AfterInvocationProcessor
	 *
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current securit context
	 * @param object $object The return object to be processed
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The joinpoint of the returning method
	 * @return boolean TRUE if access is granted, FALSE if the manager abstains from decision
	 * @throws F3_FLOW3_Security_Exception_AccessDenied If access is not granted
	 * @todo processors must also be configurable
	 */
	public function process(F3_FLOW3_Security_Context $securityContext, object $object, F3_FLOW3_AOP_JoinPointInterface $joinPoint) {

	}

	/**
	 * Returns TRUE if a appropriate after invocation processor is available to process return objects of the given classname
	 *
	 * @param string $className The classname that should be checked
	 * @return boolean TRUE if this access decision manager can decide on objects with the given classname
	 */
	public function supports($className) {

	}
}

?>