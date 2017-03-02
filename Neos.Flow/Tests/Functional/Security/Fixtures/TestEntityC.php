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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * An entity for tests
 *
 * @Flow\Entity
 */
class TestEntityC
{
    /**
     * @var string
     */
    protected $simpleStringProperty;

    /**
     * @var array<string>
     */
    protected $simpleArrayProperty;

    /**
     * @var TestEntityD
     * @ORM\OneToOne(inversedBy="relatedEntityC")
     */
    protected $relatedEntityD;

    /**
     * @var Collection<TestEntityD>
     * @ORM\OneToMany(mappedBy="manyToOneToRelatedEntityC")
     */
    protected $oneToManyToRelatedEntityD;

    /**
     * @var Collection<TestEntityD>
     * @ORM\ManyToMany
     */
    protected $manyToManyToRelatedEntityD;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->manyToOneToRelatedEntityD = new ArrayCollection();
        $this->manyToManyToRelatedEntityD = new ArrayCollection();
    }

    /**
     * @param Collection<TestEntityD> $manyToManyToRelatedEntityD
     */
    public function setManyToManyToRelatedEntityD($manyToManyToRelatedEntityD)
    {
        $this->manyToManyToRelatedEntityD = $manyToManyToRelatedEntityD;
    }

    /**
     * @return Collection<TestEntityD>
     */
    public function getManyToManyToRelatedEntityD()
    {
        return $this->manyToManyToRelatedEntityD;
    }

    /**
     * @param Collection<TestEntityD> $manyToOneToRelatedEntityD
     */
    public function setManyToOneToRelatedEntityD($manyToOneToRelatedEntityD)
    {
        $this->manyToOneToRelatedEntityD = $manyToOneToRelatedEntityD;
    }

    /**
     * @return Collection<TestEntityD>
     */
    public function getManyToOneToRelatedEntityD()
    {
        return $this->manyToOneToRelatedEntityD;
    }

    /**
     * @param TestEntityD $relatedEntityD
     */
    public function setRelatedEntityD($relatedEntityD)
    {
        $this->relatedEntityD = $relatedEntityD;
    }

    /**
     * @return TestEntityD
     */
    public function getRelatedEntityD()
    {
        return $this->relatedEntityD;
    }

    /**
     * @param array<string> $simpleArrayProperty
     */
    public function setSimpleArrayProperty($simpleArrayProperty)
    {
        $this->simpleArrayProperty = $simpleArrayProperty;
    }

    /**
     * @return array<string>
     */
    public function getSimpleArrayProperty()
    {
        return $this->simpleArrayProperty;
    }

    /**
     * @param string $simpleStringProperty
     */
    public function setSimpleStringProperty($simpleStringProperty)
    {
        $this->simpleStringProperty = $simpleStringProperty;
    }

    /**
     * @return string
     */
    public function getSimpleStringProperty()
    {
        return $this->simpleStringProperty;
    }
}
