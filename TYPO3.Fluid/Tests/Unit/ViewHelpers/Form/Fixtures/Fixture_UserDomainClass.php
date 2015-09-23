<?php
namespace TYPO3\Fluid\ViewHelpers\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Example domain class which can be used to test different view helpers, e.g. the "select" view helper.
 */
class UserDomainClass
{
    protected $id;

    protected $firstName;

    protected $lastName;

    /**
     * Constructor.
     *
     * @param int $id
     * @param string $firstName
     * @param string $lastName
     */
    public function __construct($id, $firstName, $lastName)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    /**
     * Return the ID
     *
     * @return int ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the first name
     *
     * @return string first name
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Return the last name
     *
     * @return string lastname
     */
    public function getLastName()
    {
        return $this->lastName;
    }
}
