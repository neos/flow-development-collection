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

    public function testValueObjectDeduplication()
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
        self::assertEquals(2, $query->count());
    }
}
