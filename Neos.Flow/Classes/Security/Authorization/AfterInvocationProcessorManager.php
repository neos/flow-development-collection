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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;

/**
 * The default after invocation manager that uses AfterInvocationProcessorInterface to process the return objects.
 * It resolves automatically any available AfterInvcocationProcessorInterface for the given return object and calls them.
 *
 * @Flow\Scope("singleton")
 */
class AfterInvocationProcessorManager implements AfterInvocationManagerInterface
{
    /**
     * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
     * It resolves any available AfterInvocationProcessor for the given return object and invokes them.
     * The naming convention is: [InterceptedClassName]_[InterceptedMethodName]_AfterInvocationProcessor
     *
     *
     * @param Context $securityContext The current security context
     * @param object $object The return object to be processed
     * @param JoinPointInterface $joinPoint The joinpoint of the returning method
     * @return boolean TRUE if access is granted, FALSE if the manager abstains from decision
     * @throws AccessDeniedException If access is not granted
     * @todo processors must also be configurable
     */
    public function process(Context $securityContext, $object, JoinPointInterface $joinPoint)
    {
    }

    /**
     * Returns TRUE if a appropriate after invocation processor is available to process return objects of the given classname
     *
     * @param string $className The classname that should be checked
     * @return boolean TRUE if this access decision manager can decide on objects with the given classname
     */
    public function supports($className)
    {
    }
}
