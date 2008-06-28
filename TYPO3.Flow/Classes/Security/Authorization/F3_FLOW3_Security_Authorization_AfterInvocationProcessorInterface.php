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
 * @package
 * @subpackage
 * @version $Id:$
 */

/**
 * Contract for an after invocation processor.
 *
 * @package
 * @subpackage
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Security_Authorization_AfterInvocationProcessorInterface {

	/**
	 * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
	 * It is resolved and called automatically by the after invocation processor manager. The naming convention for after invocation processors is:
	 * [InterceptedClassName]_[InterceptedMethodName]AfterInvocationProcessor
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current securit context
	 * @param object $object The return object to be processed
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The joinpoint of the returning method
	 * @return void
	 * @throws F3_FLOW3_Security_Exception_AccessDenied If access is not granted
	 */
	public function process(F3_FLOW3_Security_Context $securityContext, object $object, F3_FLOW3_AOP_JoinPointInterface $joinPoint);

	/**
	 * Returns TRUE if this after invocation processor can process return objects of the given classname
	 *
	 * @param string $className The classname that should be checked
	 * @return boolean TRUE if this access decision manager can decide on objects with the given classname
	 */
	public function supports($className);
}

?>