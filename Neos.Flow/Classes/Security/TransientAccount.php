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
use Neos\Flow\Security\Policy\RoleIdentifiers;

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
     * @var RoleIdentifiers
     */
    private $roleIdentifiers;

    /**
     * @var AuthenticationProviderName
     */
    private $authenticationProviderName;

    /**
     * Private constructor to keep this extensible with dedicated named constructors
     *
     * @param AccountIdentifier $identifier
     * @param RoleIdentifiers $roleIdentifiers
     * @param AuthenticationProviderName $authenticationProviderName
     */
    private function __construct(AccountIdentifier $identifier, RoleIdentifiers $roleIdentifiers, AuthenticationProviderName $authenticationProviderName)
    {
        $this->identifier = $identifier;
        $this->roleIdentifiers = $roleIdentifiers;
        $this->authenticationProviderName = $authenticationProviderName;
    }

    /**
     * Creates an instance of this class
     *
     * @param AccountIdentifier $identifier
     * @param RoleIdentifiers $roleIdentifiers
     * @param AuthenticationProviderName $authenticationProviderName
     * @return self
     */
    public static function create(AccountIdentifier $identifier, RoleIdentifiers $roleIdentifiers, AuthenticationProviderName $authenticationProviderName): self
    {
        return new static(
            $identifier,
            $roleIdentifiers,
            $authenticationProviderName
        );
    }

    /**
     * @inheritDoc
     */
    public function getRoleIdentifiers(): RoleIdentifiers
    {
        return $this->roleIdentifiers;
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
