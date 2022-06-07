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
 * A simple class with a bidirectional many-to-one relation for PropertyMapper test
 *
 * @Flow\Entity
 * @ORM\Table(name="property_testentity_manytoone")
 */
class TestEntityWithManyToOne
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var TestEntityWithOneToMany
     * @ORM\ManyToOne
     */
    protected $related;

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
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return TestEntityWithOneToMany
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * @param TestEntityWithOneToMany|null $related
     * @return void
     */
    public function setRelated(?TestEntityWithOneToMany $related)
    {
        $this->related = $related;
    }
}
