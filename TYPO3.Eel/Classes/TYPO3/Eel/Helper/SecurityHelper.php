<?php
namespace TYPO3\Eel\Helper;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Security\Context as SecurityContext;

/**
 * Helper for security related information
 *
 */
class SecurityHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * Get the account of the first authenticated token.
     *
     * @return \TYPO3\Flow\Security\Account|NULL
     */
    public function getAccount()
    {
        if ($this->securityContext->canBeInitialized()) {
            return $this->securityContext->getAccount();
        }

        return null;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
