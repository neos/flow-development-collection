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

/**
 * This security interceptor always grants access.
 *
 * @Flow\Scope("singleton")
 */
class AccessGrant implements InterceptorInterface
{
    /**
     * Invokes nothing, always returns true.
     *
     * @return boolean Always returns true
     */
    public function invoke()
    {
        return true;
    }
}
