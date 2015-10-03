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
class TestEntityD
{
    /**
     * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
     * @ORM\OneToMany(mappedBy="relatedEntityD")
     */
    protected $relatedEntityC;

    /**
     * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
     * @ORM\ManyToOne(inversedBy="oneToManyToRelatedEntityD")
     */
    protected $manyToOneToRelatedEntityC;

    /**
     * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC $oneToManyToRelatedEntityC
     */
    public function setOneToManyToRelatedEntityC($oneToManyToRelatedEntityC)
    {
        $this->oneToManyToRelatedEntityC = $oneToManyToRelatedEntityC;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
     */
    public function getOneToManyToRelatedEntityC()
    {
        return $this->oneToManyToRelatedEntityC;
    }

    /**
     * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC $relatedEntityC
     */
    public function setRelatedEntityC($relatedEntityC)
    {
        $this->relatedEntityC = $relatedEntityC;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
     */
    public function getRelatedEntityC()
    {
        return $this->relatedEntityC;
    }
}
