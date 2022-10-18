<?php
namespace Neos\Flow\Tests\Functional\Persistence\Doctrine;

use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;
use Neos\Flow\Tests\FunctionalTestCase;

class ValueObjectTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
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
     * This test is here to beware us from regressing again.
     *
     * If you update an object, which holds a relation to ValueObjects the deduplication listener breaks Doctrines
     * internal state of the UnitOfWork so one cannot persist again, depending on prior actions in the same request.
     *
     * The exception thrown is an ORMInvalidArgumentException with message:
     * "A managed+dirty entity test can not be scheduled for insertion."
     *
     * @test
     */
    public function valueObjectsGetDeduplicatedAndCanBePersisted()
    {
        for ($i = 0; $i < 2; $i++) {
            $testEntity = new TestEntity();
            $testEntity->setRelatedValueObject(new TestValueObject('deduplicate'));
            $this->persistenceManager->add($testEntity);
            $this->persistenceManager->persistAll();
            $this->persistenceManager->update($testEntity);
            $this->persistenceManager->persistAll();
        }
        $query = new Query(TestEntity::class);
        self::assertEquals(2, $query->count(), 'It should be exactly two TestEntities');
    }
}
