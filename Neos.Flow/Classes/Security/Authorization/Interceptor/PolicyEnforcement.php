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

use Doctrine\ORM\EntityNotFoundException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Authorization\InterceptorInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;

/**
 * This is the main security interceptor, which enforces the current security policy and is usually called by the central security aspect:
 *
 * 1. If authentication has not been performed (flag is set in the security context) the configured authentication manager is called to authenticate its tokens
 * 2. If a AuthenticationRequired exception has been thrown we look for an authentication entry point in the active tokens to redirect to authentication
 * 3. Then the configured AccessDecisionManager is called to authorize the request/action
 *
 * @Flow\Scope("singleton")
 */
class PolicyEnforcement implements InterceptorInterface
{
    /**
     * @var Context
     */
    protected $securityContext;

    /**
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * The current joinpoint
     *
     * @var JoinPointInterface
     */
    protected $joinPoint;

    /**
     * @param Context $securityContext The current security context
     * @param AuthenticationManagerInterface $authenticationManager The authentication manager
     * @param PrivilegeManagerInterface $privilegeManager The access decision manager
     */
    public function __construct(Context $securityContext, AuthenticationManagerInterface $authenticationManager, PrivilegeManagerInterface $privilegeManager)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->privilegeManager = $privilegeManager;
    }

    /**
     * Sets the current joinpoint for this interception
     *
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return void
     */
    public function setJoinPoint(JoinPointInterface $joinPoint)
    {
        $this->joinPoint = $joinPoint;
    }

    /**
     * Invokes the security interception
     *
     * @return boolean TRUE if the security checks was passed
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException if an entity could not be found (assuming it is bound to the current session), causing a redirect to the authentication entrypoint
     * @throws NoTokensAuthenticatedException if no tokens could be found and the accessDecisionManager denied access to the privilege target, causing a redirect to the authentication entrypoint
     */
    public function invoke()
    {
        $reason = '';

        $privilegeSubject = new MethodPrivilegeSubject($this->joinPoint);

        try {
            $this->authenticationManager->authenticate();
        } catch (EntityNotFoundException $exception) {
            throw new AuthenticationRequiredException('Could not authenticate. Looks like a broken session.', 1358971444, $exception);
        } catch (NoTokensAuthenticatedException $noTokensAuthenticatedException) {
            // We still need to check if the privilege is available to "Neos.Flow:Everybody".
            if ($this->privilegeManager->isGranted(MethodPrivilegeInterface::class, $privilegeSubject, $reason) === false) {
                throw new NoTokensAuthenticatedException($noTokensAuthenticatedException->getMessage() . chr(10) . $reason, $noTokensAuthenticatedException->getCode());
            }
        }

        if ($this->privilegeManager->isGranted(MethodPrivilegeInterface::class, $privilegeSubject, $reason) === false) {
            throw new AccessDeniedException($this->renderDecisionReasonMessage($reason), 1222268609);
        }
    }

    /**
     * Returns a string message, giving insights what happened during privilege evaluation.
     *
     * @param string $privilegeReasonMessage
     * @return string
     */
    protected function renderDecisionReasonMessage($privilegeReasonMessage)
    {
        if (count($this->securityContext->getRoles()) === 0) {
            $rolesMessage = 'No authenticated roles';
        } else {
            $rolesMessage = 'Authenticated roles: ' . implode(', ', array_keys($this->securityContext->getRoles()));
        }

        return sprintf('Access denied for method' . chr(10) . 'Method: %s::%s()' . chr(10) . chr(10) . '%s' . chr(10) . chr(10) . '%s', $this->joinPoint->getClassName(), $this->joinPoint->getMethodName(), $privilegeReasonMessage, $rolesMessage);
    }
}
