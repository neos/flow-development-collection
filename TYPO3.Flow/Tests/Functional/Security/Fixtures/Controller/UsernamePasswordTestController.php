<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController;

/**
 * A controller for functional testing
 */
class UsernamePasswordTestController extends AbstractAuthenticationController
{
    /**
     * @param \TYPO3\Flow\Mvc\ActionRequest $originalRequest
     * @return string
     */
    public function onAuthenticationSuccess(\TYPO3\Flow\Mvc\ActionRequest $originalRequest = null)
    {
        if ($originalRequest !== null) {
            $this->redirectToRequest($originalRequest);
        }
        $result = 'UsernamePasswordTestController success!' . chr(10);
        foreach ($this->securityContext->getRoles() as $role) {
            $result .= $role->getIdentifier() . chr(10);
        }
        return $result;
    }

    /**
     * @param \TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception
     * @throws \TYPO3\Flow\Exception
     */
    public function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = null)
    {
        throw new \TYPO3\Flow\Exception('UsernamePasswordTestController failure!', 27);
    }
}
