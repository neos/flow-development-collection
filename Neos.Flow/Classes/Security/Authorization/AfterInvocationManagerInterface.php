<?php
namespace Neos\Flow\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Context;

/**
 * Contract for an after invocation manager. It is used to check return values of a method against security rules.
 *
 */
interface AfterInvocationManagerInterface
{
    /**
     * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
     *
     * @param Context $securityContext The current security context
     * @param object $object The return object to be processed
     * @param JoinPointInterface $joinPoint The joinpoint of the returning method
     * @return boolean true if access is granted, false if the manager abstains from decision
     * @throws \Neos\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function process(Context $securityContext, $object, JoinPointInterface $joinPoint);

    /**
     * Returns true if this after invocation processor can process return objects of the given class name
     *
     * @param string $className The class name that should be checked
     * @return boolean true if this access decision manager can decide on objects with the given class name
     */
    public function supports($className);
}
