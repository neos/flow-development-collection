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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authentication\AuthenticationProviderName;
use Neos\Flow\Security\Authentication\CredentialsSource;
use Neos\Flow\Security\Policy\RoleIdentifiers;

/**
 * The default implementation of the AccountInterface that is used for database-persisted accounts
 *
 * @Flow\Entity
 * @ORM\Table(schema="neos_flow_security_account")
 * @api
 */
class PersistentAccount implements AccountInterface
{
    /**
     * @var string
     * @Flow\Identity
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
     */
    protected $accountIdentifier;

    /**
     * @var string
     * @Flow\Identity
     * @Flow\Validate(type="NotEmpty")
     */
    protected $authenticationProviderName;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $credentialsSource;

    /**
     * @var array of strings
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected $roleIdentifiers = [];

    /**
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $expirationDate;

    public function __construct()
    {
        $this->creationDate = new \DateTime();
    }

    public static function create(AccountIdentifier $accountIdentifier, AuthenticationProviderName $authenticationProviderName): AccountInterface
    {
        $account = new static();
        $account->accountIdentifier = (string) $accountIdentifier;
        $account->authenticationProviderName = (string) $authenticationProviderName;
        return $account;
    }

    /**
     * Returns the account identifier
     *
     * @return AccountIdentifier The account identifier
     * @api
     */
    public function getAccountIdentifier(): AccountIdentifier
    {
        return AccountIdentifier::fromString($this->accountIdentifier);
    }

    /**
     * Set the account identifier
     *
     * @param string $accountIdentifier The account identifier
     * @return void
     * @api
     */
    public function setAccountIdentifier($accountIdentifier)
    {
        $this->accountIdentifier = $accountIdentifier;
    }

    /**
     * Returns the authentication provider name this account corresponds to
     *
     * @return AuthenticationProviderName The authentication provider name
     * @api
     */
    public function getAuthenticationProviderName(): AuthenticationProviderName
    {
        return AuthenticationProviderName::fromString($this->authenticationProviderName);
    }

    /**
     * Set the authentication provider name this account corresponds to
     *
     * @param string $authenticationProviderName The authentication provider name
     * @return void
     * @api
     */
    public function setAuthenticationProviderName($authenticationProviderName)
    {
        $this->authenticationProviderName = $authenticationProviderName;
    }

    /**
     * Returns the credentials source
     *
     * @return CredentialsSource The credentials source
     * @api
     */
    public function getCredentialsSource(): CredentialsSource
    {
        return CredentialsSource::fromString($this->credentialsSource);
    }

    /**
     * Sets the credentials source
     *
     * @param mixed $credentialsSource The credentials source
     * @return void
     * @api
     */
    public function setCredentialsSource($credentialsSource)
    {
        $this->credentialsSource = $credentialsSource;
    }

    public function getRoleIdentifiers(): RoleIdentifiers
    {
        return RoleIdentifiers::fromArray($this->roleIdentifiers);
    }

    /**
     * Returns true if it is currently allowed to use this account for authentication.
     * Returns false if the account has expired.
     *
     * @return boolean
     * @api
     */
    public function isActive(): bool
    {
        return ($this->expirationDate === null || $this->expirationDate > new \DateTime());
    }
}
