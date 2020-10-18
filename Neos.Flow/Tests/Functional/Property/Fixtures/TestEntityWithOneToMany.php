<?php
namespace Neos\Flow\Tests\Functional\Property\Fixtures;

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
 * A simple entity with a bidirectional one-to-many relation for PropertyMapper test
 *
 * @Flow\Entity
 * @ORM\Table(name="property_testentity_onetomany")
 */
class TestEntityWithOneToMany
{
    /**
     * @var Collection<TestEntityWithManyToOne>
     * @ORM\OneToMany(mappedBy="related")
     */
    protected $values;

    /**
     * @var array
     * @Flow\Transient
     */
    protected $valuesAdded = [];

    /**
     * @var array
     * @Flow\Transient
     */
    protected $valuesRemoved = [];

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    /**
     * @return Collection<TestEntityWithManyToOne>
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param TestEntityWithManyToOne $value
     * @return void
     */
    public function addValue(TestEntityWithManyToOne $value)
    {
        if ($this->values->contains($value)) return;
        $value->setRelated($this);
        $this->valuesAdded[] = $value->getName();
        $this->values->add($value);
    }

    /**
     * @param TestEntityWithManyToOne $value
     */
    public function removeValue(TestEntityWithManyToOne $value)
    {
        if (!$this->values->contains($value)) return;
        $value->setRelated(null);
        $this->valuesRemoved[] = $value->getName();
        $this->values->removeElement($value);
    }

    public function getCollectionAdditions(): array
    {
        return $this->valuesAdded;
    }

    public function getCollectionRemovals(): array
    {
        return $this->valuesRemoved;
    }
}
