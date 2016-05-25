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
     * @var string
     * @Flow\Validate(type="\TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator")
     */
    protected $validatedProperty = '';

    /**
     * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $parentEntity
     * @return void
     */
    public function setParentEntity(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $parentEntity)
    {
        $this->parentEntity = $parentEntity;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity
     */
    public function getParentEntity()
    {
        return $this->parentEntity;
    }

    /**
     * @return string
     */
    public function getValidatedProperty()
    {
        return $this->validatedProperty;
    }

    /**
     * @param string $validatedProperty
     */
    public function setValidatedProperty($validatedProperty)
    {
        $this->validatedProperty = $validatedProperty;
    }
}
