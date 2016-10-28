<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/**
 * A sample entity for tests
 *
 * @Flow\Entity
 */
class SubEntity extends SuperEntity
{
    /**
     * @var TestEntity
     * @ORM\ManyToOne(inversedBy="subEntities")
     */
    protected $parentEntity;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $date;

    /**
     * @param TestEntity $parentEntity
     * @return void
     */
    public function setParentEntity(TestEntity $parentEntity)
    {
        $this->parentEntity = $parentEntity;
    }

    /**
     * @return TestEntity
     */
    public function getParentEntity()
    {
        return $this->parentEntity;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }
}
