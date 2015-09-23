<?php
namespace TYPO3\Flow\Security\Authorization\Resource;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Special configuration like access restrictions for persistent resources
 *
 * @Flow\Entity
 */
class SecurityPublishingConfiguration extends \TYPO3\Flow\Resource\Publishing\AbstractPublishingConfiguration
{
    /**
     * @var array
     */
    protected $allowedRoles = array();

    /**
     * Sets the roles that are allowed to see the corresponding resource
     *
     * @param array<\TYPO3\Flow\Security\Policy\Role> $allowedRoles An array of roles
     * @return void
     */
    public function setAllowedRoles(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * Returns the roles that are allowed to see the corresponding resource
     *
     * @return array An array of roles
     */
    public function getAllowedRoles()
    {
        return $this->allowedRoles;
    }
}
