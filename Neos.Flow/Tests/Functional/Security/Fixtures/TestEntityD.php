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

/**
 * An entity for tests
 *
 * @Flow\Entity
 */
class TestEntityD
{
    /**
     * @var TestEntityC
     * @ORM\OneToMany(mappedBy="relatedEntityD")
     */
    protected $relatedEntityC;

    /**
     * @var TestEntityC
     * @ORM\ManyToOne(inversedBy="oneToManyToRelatedEntityD")
     */
    protected $manyToOneToRelatedEntityC;

    /**
     * @param TestEntityC $oneToManyToRelatedEntityC
     */
    public function setOneToManyToRelatedEntityC($oneToManyToRelatedEntityC)
    {
        $this->oneToManyToRelatedEntityC = $oneToManyToRelatedEntityC;
    }

    /**
     * @return TestEntityC
     */
    public function getOneToManyToRelatedEntityC()
    {
        return $this->oneToManyToRelatedEntityC;
    }

    /**
     * @param TestEntityC $relatedEntityC
     */
    public function setRelatedEntityC($relatedEntityC)
    {
        $this->relatedEntityC = $relatedEntityC;
    }

    /**
     * @return TestEntityC
     */
    public function getRelatedEntityC()
    {
        return $this->relatedEntityC;
    }
}
