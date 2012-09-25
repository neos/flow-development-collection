<?php
namespace TYPO3\Flow\Security\Authorization;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The default after invocation manager that uses AfterInvocationProcessorInterface to process the return objects.
 * It resolves automatically any available AfterInvcocationProcessorInterface for the given return object and calls them.
 *
 * @Flow\Scope("singleton")
 */
class AfterInvocationProcessorManager implements \TYPO3\Flow\Security\Authorization\AfterInvocationManagerInterface {

	/**
	 * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
	 * It resolves any available AfterInvocationProcessor for the given return object and invokes them.
	 * The naming convention is: [InterceptedClassName]_[InterceptedMethodName]_AfterInvocationProcessor
	 *
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The current security context
	 * @param object $object The return object to be processed
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint of the returning method
	 * @return boolean TRUE if access is granted, FALSE if the manager abstains from decision
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 * @todo processors must also be configurable
	 */
	public function process(\TYPO3\Flow\Security\Context $securityContext, $object, \TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {

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