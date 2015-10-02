<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;

/**
 * Testcase for PersistenceMagicAspect
 *
 */
class PersistenceMagicAspectTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    /**
     * @test
     */
    public function aspectIntroducesUuidIdentifierToEntities()
    {
        $entity = new AnnotatedIdentitiesEntity();
        $this->assertStringMatchesFormat('%x%x%x%x%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x%x%x%x%x', $this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     */
    public function aspectDoesNotIntroduceUuidIdentifierToEntitiesWithCustomIdProperties()
    {
        $entity = new AnnotatedIdEntity();
        $this->assertNull($this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     */
    public function aspectFlagsClonedEntities()
    {
        $entity = new AnnotatedIdEntity();
        $clonedEntity = clone $entity;
        $this->assertObjectNotHasAttribute('Flow_Persistence_clone', $entity);
        $this->assertObjectHasAttribute('Flow_Persistence_clone', $clonedEntity);
        $this->assertTrue($clonedEntity->Flow_Persistence_clone);
    }
}
