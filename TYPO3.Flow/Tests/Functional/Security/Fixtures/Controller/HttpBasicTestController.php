<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A controller for functional testing of the HttpBasic Authentication provider & token
 */
class HttpBasicTestController extends \TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController
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
        $result = 'HttpBasicTestController success!' . chr(10);
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
        throw new \TYPO3\Flow\Exception('Failure Method Exception', 42);
    }
}
