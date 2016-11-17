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

use TYPO3\Flow\Package\MetaDataInterface;

/**
 * Package person party meta model
 *
 */
class Person extends AbstractParty
{
    /**
     * Company of the person
     *
     * @var string
     */
    protected $company;

    /**
     * Repository user name of the person
     *
     * @var string
     */
    protected $repositoryUserName;

    /**
     * Meta data person model constructor
     *
     * @param string $role
     * @param string $name
     * @param string $email
     * @param string $website
     * @param string $company
     * @param string $repositoryUserName
     */
    public function __construct($role, $name, $email = null, $website = null, $company = null, $repositoryUserName = null)
    {
        parent::__construct($role, $name, $email, $website);

        $this->company = $company;
        $this->repositoryUserName = $repositoryUserName;
    }

    /**
     * @return string The company of the person
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return string The repository username
     */
    public function getRepositoryUserName()
    {
        return $this->repositoryUserName;
    }

    /**
     * @return string Party type "person"
     */
    public function getPartyType()
    {
        return MetaDataInterface::PARTY_TYPE_PERSON;
    }
}
