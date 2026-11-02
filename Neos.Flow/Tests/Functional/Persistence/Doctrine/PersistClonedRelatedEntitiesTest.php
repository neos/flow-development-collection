<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEmbeddable;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for persisting cloned related entities
 */
final class PersistClonedRelatedEntitiesTest extends FunctionalTestCase
{
    /**
     * @var bool
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Fixtures\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            static::markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->testEntityRepository = $this->objectManager->get(TestEntityRepository::class);
    }

    #[Test]
    public function relatedEntitiesCanBePersistedWhenFetchedAsDoctrineProxy(): void
    {
        $entity = new TestEntity();
        $entity->setName('Andi');
        $relatedEntity = new TestEntity();
        $relatedEntity->setName('Robert');
        $entity->setRelatedEntity($relatedEntity);

        $this->testEntityRepository->add($entity);
        $this->testEntityRepository->add($relatedEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $loadedEntity = $this->testEntityRepository->findByIdentifier($entityIdentifier);

        $clonedRelatedEntity = clone $loadedEntity->getRelatedEntity();
        $this->testEntityRepository->add($clonedRelatedEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $clonedEntityIdentifier = $this->persistenceManager->getIdentifierByObject($clonedRelatedEntity);
        $clonedLoadedEntity = $this->testEntityRepository->findByIdentifier($clonedEntityIdentifier);
        self::assertInstanceOf(TestEntity::class, $clonedLoadedEntity);
    }

    #[Test]
    public function embeddablesInsideClonedProxiedEntitiesAreCorrectlyLoaded(): void
    {
        $entity = new TestEntity();
        $entity->setName('Andi');
        $relatedEntity = new TestEntity();
        $relatedEntity->setName('Robert');
        $embedded = new TestEmbeddable('Foo');
        $relatedEntity->setEmbedded($embedded);
        $valueObject = new TestValueObject('Bar');
        $relatedEntity->setRelatedValueObject($valueObject);
        $entity->setRelatedEntity($relatedEntity);

        $clonedRelatedEntity = clone $entity->getRelatedEntity();
        self::assertNotNull($clonedRelatedEntity->getEmbedded(), 'Unproxied clone embedded is null');

        $this->testEntityRepository->add($entity);
        $this->testEntityRepository->add($relatedEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $loadedEntity = $this->testEntityRepository->findByIdentifier($entityIdentifier);

        $clonedRelatedEntity = clone $loadedEntity->getRelatedEntity();
        self::assertNotNull($clonedRelatedEntity->getRelatedValueObject(), 'Proxied clone value object is null');
        self::assertNotNull($clonedRelatedEntity->getEmbedded(), 'Proxied clone embedded is null');
        self::assertEquals('Foo', $clonedRelatedEntity->getEmbedded()->getValue());
    }
}
