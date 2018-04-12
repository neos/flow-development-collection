<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

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
 * A simple entity for persistence tests
 *
 * @Flow\Entity
 * @ORM\Table(name="persistence_compsitekeytestentity")
 */
class CompositeKeyTestEntity
{

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(length=20)
     */
    protected $name = '';

    /**
     * @var TestEntity
     * @ORM\Id
     * @ORM\ManyToOne
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

    /**
     * @param TestEntity $relatedEntity
     * @return void
     */
    public function setRelatedEntity(TestEntity $relatedEntity)
    {
        $this->relatedEntity = $relatedEntity;
    }

    /**
     * @return TestEntity
     */
    public function getRelatedEntity()
    {
        return $this->relatedEntity;
    }
}
