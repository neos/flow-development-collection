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
use Neos\Flow\Security\Exception\AccessDeniedException;

/**
 * Contract for an after invocation processor.
 *
 */
interface AfterInvocationProcessorInterface
{
    /**
     * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
     * It is resolved and called automatically by the after invocation processor manager. The naming convention for after invocation processors is:
     * [InterceptedClassName]_[InterceptedMethodName]AfterInvocationProcessor
     *
     * @param Context $securityContext The current security context
     * @param object $object The return object to be processed
     * @param JoinPointInterface $joinPoint The joinpoint of the returning method
     * @return void
     * @throws AccessDeniedException If access is not granted
     */
    public function process(Context $securityContext, $object, JoinPointInterface $joinPoint);

    /**
     * Returns TRUE if this after invocation processor can process return objects of the given class name
     *
     * @param string $className The class name that should be checked
     * @return boolean TRUE if this access decision manager can decide on objects with the given class name
     */
    public function supports($className);
}
