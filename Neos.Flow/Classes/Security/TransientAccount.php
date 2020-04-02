<?php
namespace Neos\Flow\Security;

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
use Neos\Flow\Security\Authentication\AuthenticationProviderName;
use Neos\Flow\Security\Authentication\CredentialsSource;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Security\Policy\Roles;

/**
 * An account that is not persisted in the database
 *
 * This can be used for scenarios where no corresponding entity is stored in the local database, for
 * example when using a Single Sign-On service.
 *
 * @Flow\Proxy(false)
 * @api
 */
final class TransientAccount implements AccountInterface
{
    /**
     * @var AccountIdentifier
     */
    private $identifier;

    /**
     * @var Roles
     */
    private $roles;

    /**
     * @var AuthenticationProviderName
     */
    private $authenticationProviderName;

    /**
     * Private constructor to keep this extensible with dedicated named constructors
     *
     * @param AccountIdentifier $identifier
     * @param Roles $roles
     * @param AuthenticationProviderName $authenticationProviderName
     */
    private function __construct(AccountIdentifier $identifier, Roles $roles, AuthenticationProviderName $authenticationProviderName)
    {
        $this->identifier = $identifier;
        $this->roles = $roles;
        $this->authenticationProviderName = $authenticationProviderName;
    }

    /**
     * Creates an instance of this class
     *
     * @param AccountIdentifier $identifier
     * @param Roles $roles
     * @param AuthenticationProviderName $authenticationProviderName
     * @return self
     */
    public static function create(AccountIdentifier $identifier, Roles $roles, AuthenticationProviderName $authenticationProviderName): self
    {
        return new static(
            $identifier,
            $roles,
            $authenticationProviderName
        );
    }

    /**
     * @inheritDoc
     */
    public function getAccountIdentifier(): AccountIdentifier
    {
        return $this->identifier;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): Roles
    {
        return $this->roles;
    }

    /**
     * @inheritDoc
     */
    public function hasRole(Role $role): bool
    {
        return $this->roles->has($role);
    }

    /**
     * @inheritDoc
     */
    public function getAuthenticationProviderName(): AuthenticationProviderName
    {
        return $this->authenticationProviderName;
    }

    /**
     * Transient accounts have an empty CredentialsSource
     */
    public function getCredentialsSource(): CredentialsSource
    {
        return CredentialsSource::empty();
    }

    /**
     * Transient accounts are always active
     */
    public function isActive(): bool
    {
        return true;
    }
}
