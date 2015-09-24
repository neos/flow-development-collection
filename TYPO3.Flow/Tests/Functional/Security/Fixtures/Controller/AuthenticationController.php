<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A controller for functional testing
 */
class AuthenticationController extends \TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController
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
        return 'Authentication Success returned!';
    }

    /**
     * @param \TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception
     * @throws \TYPO3\Flow\Exception
     */
    public function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = null)
    {
        throw new \TYPO3\Flow\Exception('Failure Method Exception', 42);
    }
}
