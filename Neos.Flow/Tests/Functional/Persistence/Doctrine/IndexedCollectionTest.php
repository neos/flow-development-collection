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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Test for Doctrine indexed Collections
 * @Flow\Scope("prototype")
 */
class IndexedCollectionTest extends FunctionalTestCase
{
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    /**
     * This tests calls two indexed Relations and ensure that indexes are restored after fetching from persistence
     *
     * @test
     */
    public function collectionsWithIndexAttributeAreIndexed()
    {
        $entityWithIndexedRelation = new Fixtures\EntityWithIndexedRelation();
        for ($i = 0; $i < 3; $i++) {
            $annotatedIdentitiesEntity = new Fixtures\AnnotatedIdentitiesEntity();
            $annotatedIdentitiesEntity->setAuthor('Author' . ((string) $i));
            $annotatedIdentitiesEntity->setTitle('Author' . ((string) $i));
            $entityWithIndexedRelation->getAnnotatedIdentitiesEntities()->add($annotatedIdentitiesEntity);
        }

        $entityWithIndexedRelation->setRelatedIndexEntity('test', new Fixtures\RelatedIndexEntity());

        $this->persistenceManager->add($entityWithIndexedRelation);
        $this->persistenceManager->persistAll();
        $id = $this->persistenceManager->getIdentifierByObject($entityWithIndexedRelation);

        // Reset persistence manager to make sure fresh instances will be created
        $this->persistenceManager->clearState();
        unset($entityWithIndexedRelation);

        $entityWithIndexedRelation = $this->persistenceManager->getObjectByIdentifier($id, Fixtures\EntityWithIndexedRelation::class);
        for ($i = 0; $i < 3; $i++) {
            self::assertArrayHasKey('Author' . (string) $i, $entityWithIndexedRelation->getAnnotatedIdentitiesEntities());
        }
        self::assertArrayNotHasKey(0, $entityWithIndexedRelation->getAnnotatedIdentitiesEntities());

        self::assertArrayHasKey('test', $entityWithIndexedRelation->getRelatedIndexEntities());
        self::assertArrayNotHasKey(0, $entityWithIndexedRelation->getRelatedIndexEntities());
        self::assertInstanceOf(Fixtures\RelatedIndexEntity::class, $entityWithIndexedRelation->getRelatedIndexEntities()->get('test'));
    }
}
