<?php
namespace TYPO3\Flow\Security\Authorization;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Contract for a security interceptor.
 */
interface InterceptorInterface
{
    /**
     * Invokes the security interception (e.g. calls a \TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface)
     *
     * @return boolean TRUE if the security checks was passed
     */
    public function invoke();
}
