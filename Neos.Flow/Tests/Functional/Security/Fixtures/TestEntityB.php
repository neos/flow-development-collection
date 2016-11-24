<?php
namespace Neos\Flow\Tests\Functional\Security\Fixtures;

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
use Neos\Flow\Security\Account;

/**
 * An entity for tests
 *
 * @Flow\Entity
 */
class TestEntityB
{
    /**
     * @var string
     */
    protected $stringValue;

    /**
     * @var TestEntityA
     * @ORM\OneToOne(mappedBy="relatedEntityB")
     */
    protected $relatedEntityA;

    /**
     * @var Account
     * @ORM\ManyToOne
     */
    protected $ownerAccount;

    /**
     * Constructor
     *
     * @param string $stringValue
     */
    public function __construct($stringValue)
    {
        $this->stringValue = $stringValue;
    }

    /**
     * @param string $stringValue
     */
    public function setStringValue($stringValue)
    {
        $this->stringValue = $stringValue;
    }

    /**
     * @return string
     */
    public function getStringValue()
    {
        return $this->stringValue;
    }

    /**
     * @param TestEntityA $relatedEntityA
     */
    public function setRelatedEntityA($relatedEntityA)
    {
        $this->relatedEntityA = $relatedEntityA;
    }

    /**
     * @return TestEntityA
     */
    public function getRelatedEntityA()
    {
        return $this->relatedEntityA;
    }

    /**
     * @param Account $ownerAccount
     */
    public function setOwnerAccount($ownerAccount)
    {
        $this->ownerAccount = $ownerAccount;
    }

    /**
     * @return Account
     */
    public function getOwnerAccount()
    {
        return $this->ownerAccount;
    }
}
