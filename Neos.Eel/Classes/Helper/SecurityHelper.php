<?php
namespace Neos\Eel\Helper;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context as SecurityContext;

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
     * @return Account|NULL
     */
    public function getAccount()
    {
        if ($this->securityContext->canBeInitialized()) {
            return $this->securityContext->getAccount();
        }

        return null;
    }

    /**
     * Returns TRUE, if at least one of the currently authenticated accounts holds
     * a role with the given identifier, also recursively.
     *
     * @param string $roleIdentifier The string representation of the role to search for
     * @return boolean TRUE, if a role with the given string representation was found
     */
    public function hasRole($roleIdentifier)
    {
        if ($roleIdentifier === 'Neos.Flow:Everybody') {
            return true;
        }

        if ($this->securityContext->canBeInitialized()) {
            return $this->securityContext->hasRole($roleIdentifier);
        }

        return false;
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
