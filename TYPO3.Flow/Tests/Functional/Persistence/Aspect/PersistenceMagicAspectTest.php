<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;

/**
 * Testcase for PersistenceMagicAspect
 *
 */
class PersistenceMagicAspectTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
	}

	/**
	 * @test
	 */
	public function aspectIntroducesUuidIdentifierToEntities() {
		$entity = new AnnotatedIdentitiesEntity();
		$this->assertStringMatchesFormat('%x%x%x%x%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x%x%x%x%x', $this->persistenceManager->getIdentifierByObject($entity));
	}

	/**
	 * @test
	 */
	public function aspectDoesNotIntroduceUuidIdentifierToEntitiesWithCustomIdProperties() {
		$entity = new AnnotatedIdEntity();
		$this->assertNull($this->persistenceManager->getIdentifierByObject($entity));
	}

	/**
	 * @test
	 */
	public function aspectFlagsClonedEntities() {
		$entity = new AnnotatedIdEntity();
		$clonedEntity = clone $entity;
		$this->assertObjectNotHasAttribute('Flow_Persistence_clone', $entity);
		$this->assertObjectHasAttribute('Flow_Persistence_clone', $clonedEntity);
		$this->assertTrue($clonedEntity->Flow_Persistence_clone);
	}
}
?>
