<?php
namespace TYPO3\Flow\Security\Authorization;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Contract for an after invocation manager. It is used to check return values of a method against security rules.
 *
 */
interface AfterInvocationManagerInterface
{
    /**
     * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The current security context
     * @param object $object The return object to be processed
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint of the returning method
     * @return boolean TRUE if access is granted, FALSE if the manager abstains from decision
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function process(\TYPO3\Flow\Security\Context $securityContext, $object, \TYPO3\Flow\Aop\JoinPointInterface $joinPoint);

    /**
     * Returns TRUE if this after invocation processor can process return objects of the given class name
     *
     * @param string $className The class name that should be checked
     * @return boolean TRUE if this access decision manager can decide on objects with the given class name
     */
    public function supports($className);
}
