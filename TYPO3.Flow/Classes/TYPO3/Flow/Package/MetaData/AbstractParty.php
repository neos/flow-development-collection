<?php
namespace TYPO3\Flow\Package\MetaData;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Party meta model for persons and companies
 *
 */
abstract class AbstractParty
{
    /**
     * The party role
     *
     * @var string
     */
    protected $role;

    /**
     * Name of the party
     *
     * @var string
     */
    protected $name;

    /**
     * Email of the party
     *
     * @var string
     */
    protected $email;

    /**
     * Website of the party
     *
     * @var string
     */
    protected $website;

    /**
     * Meta data party model constructor
     *
     * @param string $role
     * @param string $name
     * @param string $email
     * @param string $website
     */
    public function __construct($role, $name, $email = null, $website = null)
    {
        $this->role = $role;
        $this->name = $name;
        $this->email = $email;
        $this->website = $website;
    }

    /**
     * @return string The role of the party
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string The name of the party
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string The email of the party
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string The website of the party
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Get the party type (MetaData\PARTY_TYPE_PERSON, MetaData\PARTY_TYPE_COMPANY)
     *
     * @return string The type of the party (person, company)
     */
    abstract public function getPartyType();
}
