<?php
namespace TYPO3\Flow\Package\MetaData;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Package person party meta model
 *
 */
class Person extends \TYPO3\Flow\Package\MetaData\AbstractParty
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
        return \TYPO3\Flow\Package\MetaDataInterface::PARTY_TYPE_PERSON;
    }
}
