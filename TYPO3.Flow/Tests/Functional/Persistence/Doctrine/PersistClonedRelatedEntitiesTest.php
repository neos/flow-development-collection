<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Doctrine;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEmbeddable;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;

/**
 * Testcase for persisting cloned related entities
 */
class PersistClonedRelatedEntitiesTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository;
     */
    protected $testEntityRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->testEntityRepository = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository::class);
    }

    /**
     * @test
     */
    public function relatedEntitiesCanBePersistedWhenFetchedAsDoctrineProxy()
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
        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity::class, $clonedLoadedEntity);
    }

    /**
     * @test
     */
    public function embeddablesInsideClonedProxiedEntitiesAreCorrectlyLoaded()
    {
        $this->markTestSkipped('This is possibly a bug of Doctrine');
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
