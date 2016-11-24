<?php
namespace Neos\Flow\Tests\Functional\Security\Fixtures\Controller;

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;

/**
 * A controller for functional testing of the HttpBasic Authentication provider & token
 */
class HttpBasicTestController extends AbstractAuthenticationController
{
    /**
     * @param ActionRequest $originalRequest
     * @return string
     */
    public function onAuthenticationSuccess(ActionRequest $originalRequest = null)
    {
        if ($originalRequest !== null) {
            $this->redirectToRequest($originalRequest);
        }
        $result = 'HttpBasicTestController success!' . chr(10);
        foreach ($this->securityContext->getRoles() as $role) {
            $result .= $role->getIdentifier() . chr(10);
        }
        return $result;
    }

    /**
     * @param AuthenticationRequiredException $exception
     * @throws FlowException
     */
    public function onAuthenticationFailure(AuthenticationRequiredException $exception = null)
    {
        throw new FlowException('Failure Method Exception', 42);
    }
}
