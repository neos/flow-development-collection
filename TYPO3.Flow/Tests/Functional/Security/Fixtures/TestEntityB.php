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
     * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityA
     * @ORM\OneToOne(mappedBy="relatedEntityB")
     */
    protected $relatedEntityA;

    /**
     * @var \TYPO3\Flow\Security\Account
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
     * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityA $relatedEntityA
     */
    public function setRelatedEntityA($relatedEntityA)
    {
        $this->relatedEntityA = $relatedEntityA;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityA
     */
    public function getRelatedEntityA()
    {
        return $this->relatedEntityA;
    }

    /**
     * @param \TYPO3\Flow\Security\Account $ownerAccount
     */
    public function setOwnerAccount($ownerAccount)
    {
        $this->ownerAccount = $ownerAccount;
    }

    /**
     * @return \TYPO3\Flow\Security\Account
     */
    public function getOwnerAccount()
    {
        return $this->ownerAccount;
    }
}
