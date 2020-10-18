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
 * A simple class with a bidirectional one-to-many relation for PropertyMapper test
 * @Flow\Entity
 * @ORM\Table(name="property_testentity_manytomany")
 */
class TestEntityWithUnidirectionalManyToMany
{
    /**
     * @var Collection<TestEntityWithoutRelation>
     * @ORM\ManyToMany
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
     * @return Collection<TestEntityWithoutRelation>
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param TestEntityWithoutRelation $value
     * @return void
     */
    public function addValue(TestEntityWithoutRelation $value)
    {
        $this->valuesAdded[] = $value->getName();
        $this->values->add($value);
    }

    /**
     * @param TestEntityWithoutRelation $value
     */
    public function removeValue(TestEntityWithoutRelation $value)
    {
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
