<?php
namespace Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * A test entity which is used to test Fluid forms in combination with
 * property mapping
 *
 * @Flow\Entity
 */
class User
{
    /**
     * @var string
     * @Flow\Validate(type="EmailAddress")
     */
    protected $emailAddress;

    /**
     * @var Location
     */
    protected $location;

    public function __construct()
    {
        $this->location = new Location();
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string $email
     */
    public function setEmailAddress($email)
    {
        $this->emailAddress = $email;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;
    }
}
