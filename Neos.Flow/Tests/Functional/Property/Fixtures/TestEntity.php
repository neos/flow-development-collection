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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A simple entity for PropertyMapper test
 *
 * @Flow\Entity
 * @ORM\Table(name="property_testentity")
 */
class TestEntity implements TestEntityInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     *
     * @var integer
     */
    protected $age;

    /**
     *
     * @var float
     */
    protected $averageNumberOfKids;

    /**
     * @var TestEntityInterface
     * @Flow\Transient
     */
    protected $relatedEntity;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function getAverageNumberOfKids()
    {
        return $this->averageNumberOfKids;
    }

    public function setAverageNumberOfKids($averageNumberOfKids)
    {
        $this->averageNumberOfKids = $averageNumberOfKids;
    }

    /**
     * Set the age by specifying the year of birth
     *
     * For test purposes the reference year is hard-coded to 2013.
     *
     * @param integer $yearOfBirth
     */
    public function setYearOfBirth($yearOfBirth)
    {
        $this->setAge(2013 - $yearOfBirth);
    }

    /**
     * @param TestEntityInterface $relatedEntity
     * @return void
     */
    public function setRelatedEntity(TestEntityInterface $relatedEntity)
    {
        $this->relatedEntity = $relatedEntity;
    }

    /**
     * @return TestEntityInterface
     */
    public function getRelatedEntity()
    {
        return $this->relatedEntity;
    }
}
