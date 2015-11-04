<?php
namespace TYPO3\Flow\Security\Authorization\Interceptor;

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
 * This security interceptor always denys access.
 *
 * @Flow\Scope("singleton")
 */
class AccessDeny implements \TYPO3\Flow\Security\Authorization\InterceptorInterface
{
    /**
     * Invokes nothing, always throws an AccessDenied Exception.
     *
     * @return boolean Always returns FALSE
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException
     */
    public function invoke()
    {
        throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('You are not allowed to perform this action.', 1216919280);
    }
}
