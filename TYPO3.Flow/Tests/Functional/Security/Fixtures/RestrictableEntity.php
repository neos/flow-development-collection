<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security;

/**
 * A restrictable entity for tests
 *
 * @Flow\Entity
 */
class RestrictableEntity
{
    /**
     * @var boolean
     */
    protected $hidden = false;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Security\Account
     * @ORM\ManyToOne
     */
    protected $ownerAccount;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $deletedOn;

    /**
     * Constructor
     *
     * @param string $name The name of the entity
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean Returns TRUE, if this entity is hidden
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @param boolean $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Security\Account $ownerAccount
     */
    public function setOwnerAccount($ownerAccount)
    {
        $this->ownerAccount = $ownerAccount;
    }

    /**
     * @return Security\Account
     */
    public function getOwnerAccount()
    {
        return $this->ownerAccount;
    }

    public function delete()
    {
        $this->deletedOn = new \DateTime();
    }
}
