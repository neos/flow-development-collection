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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Exception\InvalidAuthenticationStatusException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Utility\Now;

/**
 * An account model
 *
 * @Flow\Entity
 * @api
 */
class Account
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
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $expirationDate;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $lastSuccessfulAuthenticationDate;

    /**
     * @var integer
     * @ORM\Column(nullable=true)
     */
    protected $failedAuthenticationCount;

    /**
     * @var array of strings
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected $roleIdentifiers = [];

    /**
     * @Flow\Transient
     * @var array<Role>
     */
    protected $roles;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * Upon creation the creationDate property is initialized.
     */
    public function __construct()
    {
        $this->creationDate = new \DateTime();
    }

    /**
     * Initializes the roles field by fetching the role objects referenced by the roleIdentifiers
     *
     * @return void
     */
    protected function initializeRoles()
    {
        if ($this->roles !== null) {
            return;
        }
        $this->roles = [];
        foreach ($this->roleIdentifiers as $key => $roleIdentifier) {
            // check for and clean up roles no longer available
            if ($this->policyService->hasRole($roleIdentifier)) {
                $this->roles[$roleIdentifier] = $this->policyService->getRole($roleIdentifier);
            } else {
                unset($this->roleIdentifiers[$key]);
            }
        }
    }

    /**
     * Returns the account identifier
     *
     * @return string The account identifier
     * @api
     */
    public function getAccountIdentifier()
    {
        return $this->accountIdentifier;
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
     * @return string The authentication provider name
     * @api
     */
    public function getAuthenticationProviderName()
    {
        return $this->authenticationProviderName;
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
     * @return mixed The credentials source
     * @api
     */
    public function getCredentialsSource()
    {
        return $this->credentialsSource;
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

    /**
     * Returns the roles this account has assigned
     *
     * @return array<Role> The assigned roles, indexed by role identifier
     * @api
     */
    public function getRoles()
    {
        $this->initializeRoles();
        return $this->roles;
    }

    /**
     * Sets the roles for this account
     *
     * @param array<Role> $roles An array of Policy\Role objects
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function setRoles(array $roles)
    {
        $this->roleIdentifiers = [];
        $this->roles = [];
        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                throw new \InvalidArgumentException(sprintf('setRoles() only accepts an array of %s instances, given: "%s"', Role::class, gettype($role)), 1397125997);
            }
            $this->addRole($role);
        }
    }

    /**
     * Return if the account has a certain role
     *
     * @param Role $role
     * @return boolean
     * @api
     */
    public function hasRole(Role $role)
    {
        $this->initializeRoles();
        return array_key_exists($role->getIdentifier(), $this->roles);
    }

    /**
     * Adds a role to this account
     *
     * @param Role $role
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function addRole(Role $role)
    {
        if ($role->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Abstract roles can\'t be assigned to accounts directly, but the role "%s" is marked abstract', $role->getIdentifier()), 1399900657);
        }
        $this->initializeRoles();
        if (!$this->hasRole($role)) {
            $roleIdentifier = $role->getIdentifier();
            $this->roleIdentifiers[] = $roleIdentifier;
            $this->roles[$roleIdentifier] = $role;
        }
    }

    /**
     * Removes a role from this account
     *
     * @param Role $role
     * @return void
     * @api
     */
    public function removeRole(Role $role)
    {
        $this->initializeRoles();
        if ($this->hasRole($role)) {
            $roleIdentifier = $role->getIdentifier();
            unset($this->roles[$roleIdentifier]);
            $identifierIndex = array_search($roleIdentifier, $this->roleIdentifiers);
            unset($this->roleIdentifiers[$identifierIndex]);
        }
    }

    /**
     * Returns the date on which this account has been created.
     *
     * @return \DateTime
     * @api
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Returns the date on which this account has expired or will expire. If no expiration date has been set, NULL
     * is returned.
     *
     * @return \DateTime
     * @api
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Sets the date on which this account will become inactive
     *
     * @param \DateTime $expirationDate
     * @return void
     * @api
     */
    public function setExpirationDate(\DateTime $expirationDate = null)
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * @return integer
     * @api
     */
    public function getFailedAuthenticationCount()
    {
        return $this->failedAuthenticationCount;
    }

    /**
     * @return \DateTime
     * @api
     */
    public function getLastSuccessfulAuthenticationDate()
    {
        return $this->lastSuccessfulAuthenticationDate;
    }

    /**
     * Sets the authentication status. Usually called by the responsible \Neos\Flow\Security\Authentication\AuthenticationManagerInterface
     *
     * @param integer $authenticationStatus One of WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL
     * @return void
     * @throws InvalidAuthenticationStatusException
     */
    public function authenticationAttempted($authenticationStatus)
    {
        if ($authenticationStatus === TokenInterface::WRONG_CREDENTIALS) {
            $this->failedAuthenticationCount++;
        } elseif ($authenticationStatus === TokenInterface::AUTHENTICATION_SUCCESSFUL) {
            $this->lastSuccessfulAuthenticationDate = new \DateTime();
            $this->failedAuthenticationCount = 0;
        } else {
            throw new InvalidAuthenticationStatusException('Invalid authentication status.', 1449151375);
        }
    }

    /**
     * Returns TRUE if it is currently allowed to use this account for authentication.
     * Returns FALSE if the account has expired.
     *
     * @return boolean
     * @api
     */
    public function isActive()
    {
        return ($this->expirationDate === null || $this->expirationDate > $this->now);
    }
}
