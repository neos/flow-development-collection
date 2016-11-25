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
 * @ORM\Table(name="property_testentity_immutable")
 */
class TestEntityWithImmutableProperty
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
     * @param string $name Sets the immutable name property that has no setter.
     */
    public function __construct($name)
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
}
