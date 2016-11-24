<?php
namespace Neos\Flow\Security\Authorization\Interceptor;

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
use Neos\Flow\Security\Authorization\AfterInvocationManagerInterface;
use Neos\Flow\Security\Authorization\InterceptorInterface;
use Neos\Flow\Security\Context;

/**
 * This is the second main security interceptor, which enforces the current security policy for return values and is usually applied over AOP:
 *
 * 1. We call the AfterInvocationManager with the method's return value as parameter
 * 2. If we had a "run as" support, we would have to reset the security context
 * 3. If a PermissionDeniedException was thrown we look for any an authentication entry point in the active tokens to redirect to authentication
 * 4. Then the value is returned to the caller
 *
 */
class AfterInvocation implements InterceptorInterface
{
    /**
     * @var AfterInvocationManagerInterface
     */
    protected $afterInvocationManager = null;

    /**
     * Result of the (probably intercepted) target method
     * @var mixed
     */
    protected $result;

    /**
     * Constructor.
     *
     * @param Context $securityContext The current security context
     * @param AfterInvocationManagerInterface $afterInvocationManager The after invocation manager
     */
    public function __construct(Context $securityContext, AfterInvocationManagerInterface $afterInvocationManager)
    {
    }

    /**
     * Sets the current joinpoint for this interception
     *
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return void
     */
    public function setJoinPoint(JoinPointInterface $joinPoint)
    {
    }

    /**
     * Sets the result (return object) of the intercepted method
     *
     * @param mixed $result The result of the intercepted method
     * @return void
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Invokes the security interception
     *
     * @return boolean TRUE if the security checks was passed
     * @todo Implement interception logic
     */
    public function invoke()
    {
        return $this->result;
    }
}
