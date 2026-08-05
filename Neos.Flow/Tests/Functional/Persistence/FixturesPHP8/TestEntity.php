<?php
namespace Neos\Flow\Tests\Functional\Persistence\FixturesPHP8;

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
use Neos\Flow\Tests\Functional\Persistence\FixturesPHP8\SubEntity as ImportedSubEntity;

/**
 * A simple entity for persistence tests
 */
#[Flow\Entity]
#[ORM\Table(name: 'persistence_php8_testentity')]
class TestEntity
{
    #[Flow\Inject]
    protected ObjectManagerInterface $objectManager;

    #[Flow\Validate(type: "StringLength", options:["minimum" => 3])]
    protected string $name = '';

    #[ORM\ManyToOne]
    protected TestEntity $relatedEntity;

    /**
     * @var Collection<ImportedSubEntity>
     */
    #[ORM\OneToMany(mappedBy: "parentEntity", cascade: ["all"])]
    protected Collection $subEntities;

    #[ORM\ManyToOne]
    protected TestValueObject $relatedValueObject;

    #[Flow\Validate(type: "NotEmpty", validationGroups: ["SomeOther"])]
    protected string $description = 'This is some text';

    protected TestEmbeddedValueObject $embeddedValueObject;

    protected array $arrayProperty = [];

    #[ORM\Embedded(class: "Neos\Flow\Tests\Functional\Persistence\FixturesPHP8\TestEmbeddable")]
    protected TestEmbeddable $embedded;

    #[ORM\Column(type: 'string', enumType: EnumForAProperty::class)]
    protected EnumForAProperty $enumForAProperty = EnumForAProperty::IS_A;

    /**
     * Constructor
     */
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

    public function setArrayProperty(array $arrayProperty): void
    {
        $this->arrayProperty = $arrayProperty;
    }

    public function getArrayProperty(): array
    {
        return $this->arrayProperty;
    }

    public function sayHello(): string
    {
        return 'Hello';
    }

    public function setRelatedEntity(TestEntity $relatedEntity): void
    {
        $this->relatedEntity = $relatedEntity;
    }

    public function getRelatedEntity(): TestEntity
    {
        return $this->relatedEntity;
    }

    /**
     * @param Collection<ImportedSubEntity> $subEntities
     */
    public function setSubEntities(Collection $subEntities): void
    {
        $this->subEntities = $subEntities;
    }

    public function addSubEntity(ImportedSubEntity $subEntity): void
    {
        $this->subEntities->add($subEntity);
    }

    /**
     * @return Collection<ImportedSubEntity>
     */
    public function getRelatedEntities(): Collection
    {
        return $this->subEntities;
    }

    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    public function setRelatedValueObject(TestValueObject $relatedValueObject): void
    {
        $this->relatedValueObject = $relatedValueObject;
    }

    public function getRelatedValueObject(): TestValueObject
    {
        return $this->relatedValueObject;
    }

    public function getEmbedded(): TestEmbeddable
    {
        return $this->embedded;
    }

    public function setEmbedded(TestEmbeddable $embedded): void
    {
        $this->embedded = $embedded;
    }

    public function setEmbeddedValueObject(TestEmbeddedValueObject $embeddedValueObject): void
    {
        $this->embeddedValueObject = $embeddedValueObject;
    }

    public function getEmbeddedValueObject(): TestEmbeddedValueObject
    {
        return $this->embeddedValueObject;
    }

    public function getEnumForAProperty(): EnumForAProperty
    {
        return $this->enumForAProperty;
    }

    public function setEnumForAProperty(EnumForAProperty $enumForAProperty): void
    {
        $this->enumForAProperty = $enumForAProperty;
    }
}
