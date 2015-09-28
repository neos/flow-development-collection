<?php
namespace TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
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
}
