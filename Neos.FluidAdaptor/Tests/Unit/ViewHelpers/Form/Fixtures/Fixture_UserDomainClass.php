<?php
namespace Neos\FluidAdaptor\ViewHelpers\Fixtures;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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

    /**
     * @return \ArrayObject
     */
    public function getInterests()
    {
        return new \ArrayObject(array(
            'value1',
            'value3',
        ));
    }
}
