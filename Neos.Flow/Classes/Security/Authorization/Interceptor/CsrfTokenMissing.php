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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\InterceptorInterface;
use Neos\Flow\Security\Exception\AccessDeniedException;

/**
 * This security interceptor always denys access.
 *
 * @Flow\Scope("singleton")
 */
class CsrfTokenMissing implements InterceptorInterface
{
    /**
     * Invokes nothing, always throws an AccessDenied Exception.
     *
     * @return boolean Always returns false
     * @throws AccessDeniedException
     */
    public function invoke()
    {
        throw new AccessDeniedException('The action could not be executed because you supplied no or the wrong CSRF protection token.', 1486500872);
    }
}
