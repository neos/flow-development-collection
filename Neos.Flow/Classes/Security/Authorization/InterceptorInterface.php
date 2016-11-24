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

/**
 * Contract for a security interceptor.
 */
interface InterceptorInterface
{
    /**
     * Invokes the security interception (e.g. calls a PrivilegeManagerInterface)
     *
     * @return boolean TRUE if the security checks was passed
     */
    public function invoke();
}
