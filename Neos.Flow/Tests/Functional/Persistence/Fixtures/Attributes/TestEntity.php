<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures\Attributes;

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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\SubEntity as ImportedSubEntity;

/**
 * A simple entity for persistence tests
 */
#[ORM\Table(name: 'persistence_attributes_testentity')]
#[Flow\Entity]
class TestEntity
{
    /**
     * @var ObjectManagerInterface
     */
    #[Flow\Inject]
    protected $objectManager;

    #[Flow\Validate(type: 'StringLength', options: ['minimum' => 3])]
    protected string $name = '';

    #[ORM\ManyToOne(targetEntity: TestEntity::class)]
    protected TestEntity $relatedEntity;

    #[ORM\OneToMany(targetEntity: ImportedSubEntity::class, mappedBy: 'parentEntity', cascade: ['all'])]
    protected Collection $subEntities;

    #[ORM\ManyToOne]
    protected TestValueObject $relatedValueObject;

    #[Flow\Validate(type: 'NotEmpty', validationGroups: ['SomeOther'])]
    protected string $description = 'This is some text';

    protected TestEmbeddedValueObject $embeddedValueObject;

    protected array $arrayProperty = [];

    #[ORM\Embedded(class: TestEmbeddable::class)]
    protected TestEmbeddable $embedded;

    public function __construct()
    {
        $this->subEntities = new ArrayCollection();
        $this->embedded = new TestEmbeddable('');
        $this->embeddedValueObject = new TestEmbeddedValueObject();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRelatedEntity(): TestEntity
    {
        return $this->relatedEntity;
    }

    public function setRelatedEntity(TestEntity $relatedEntity): void
    {
        $this->relatedEntity = $relatedEntity;
    }

    /**
     * @return Collection<ImportedSubEntity>
     */
    public function getSubEntities(): Collection
    {
        return $this->subEntities;
    }

    public function setSubEntities(Collection $subEntities): void
    {
        $this->subEntities = $subEntities;
    }

    public function getRelatedValueObject(): TestValueObject
    {
        return $this->relatedValueObject;
    }

    public function setRelatedValueObject(TestValueObject $relatedValueObject): void
    {
        $this->relatedValueObject = $relatedValueObject;
    }

    public function getEmbeddedValueObject(): TestEmbeddedValueObject
    {
        return $this->embeddedValueObject;
    }

    public function setEmbeddedValueObject(TestEmbeddedValueObject $embeddedValueObject): void
    {
        $this->embeddedValueObject = $embeddedValueObject;
    }

    public function getArrayProperty(): array
    {
        return $this->arrayProperty;
    }

    public function sayHello(): string
    {
        return 'Hello';
    }

    public function setArrayProperty(array $arrayProperty): void
    {
        $this->arrayProperty = $arrayProperty;
    }

    public function getEmbedded(): TestEmbeddable
    {
        return $this->embedded;
    }

    public function setEmbedded(TestEmbeddable $embedded): void
    {
        $this->embedded = $embedded;
    }
}
