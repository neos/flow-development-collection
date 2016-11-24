<?php
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
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for persisting cloned related entities
 */
class PersistClonedRelatedEntitiesTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Fixtures\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->testEntityRepository = $this->objectManager->get(Fixtures\TestEntityRepository::class);
    }

    /**
     * @test
     */
    public function relatedEntitiesCanBePersistedWhenFetchedAsDoctrineProxy()
    {
        $entity = new Fixtures\TestEntity();
        $entity->setName('Andi');
        $relatedEntity = new Fixtures\TestEntity();
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
        $this->assertInstanceOf(Fixtures\TestEntity::class, $clonedLoadedEntity);
    }

    /**
     * @test
     */
    public function embeddablesInsideClonedProxiedEntitiesAreCorrectlyLoaded()
    {
        $this->markTestSkipped('This is possibly a bug of Doctrine');
        $entity = new Fixtures\TestEntity();
        $entity->setName('Andi');
        $relatedEntity = new Fixtures\TestEntity();
        $relatedEntity->setName('Robert');
        $embedded = new Fixtures\TestEmbeddable('Foo');
        $relatedEntity->setEmbedded($embedded);
        $valueObject = new Fixtures\TestValueObject('Bar');
        $relatedEntity->setRelatedValueObject($valueObject);
        $entity->setRelatedEntity($relatedEntity);

        $clonedRelatedEntity = clone $entity->getRelatedEntity();
        $this->assertNotNull($clonedRelatedEntity->getEmbedded(), 'Unproxied clone embedded is null');

        $this->testEntityRepository->add($entity);
        $this->testEntityRepository->add($relatedEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $loadedEntity = $this->testEntityRepository->findByIdentifier($entityIdentifier);

        $clonedRelatedEntity = clone $loadedEntity->getRelatedEntity();
        $this->assertNotNull($clonedRelatedEntity->getRelatedValueObject(), 'Proxied clone value object is null');
        $this->assertNotNull($clonedRelatedEntity->getEmbedded(), 'Proxied clone embedded is null');
        $this->assertEquals('Foo', $clonedRelatedEntity->getEmbedded()->getValue());
    }
}
