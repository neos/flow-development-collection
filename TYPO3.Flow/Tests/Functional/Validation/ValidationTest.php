<?php
namespace TYPO3\Flow\Tests\Functional\Validation;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the TYPO3 Flow Validation Framework
 *
 */
class ValidationTest extends FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository
	 */
	protected $testEntityRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}

		$this->testEntityRepository = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository');

		$this->registerRoute('post', 'test/validation/entity/{@action}', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\Fixtures',
			'@controller' => 'Entity',
			'@format' =>'html'
		));
	}

	/**
	 * The ValidationResolver has a 1st level cache. This test ensures that this cache is flushed between two requests.
	 *
	 * @test
	 */
	public function validationIsEnforcedOnSuccessiveRequests() {
		$entity = new TestEntity();
		$entity->setName('Some Name');
		$this->testEntityRepository->add($entity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
		$validArguments = array('entity' => array('__identity' => $entityIdentifier, 'name' => 'long enough name'));
		$response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $validArguments);
		$this->assertSame('Entity "long enough name" updated', $response->getContent());

		$invalidArguments = array('entity' => array('__identity' => $entityIdentifier, 'name' => 'xx'));
		$response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $invalidArguments);
		$this->assertSame('An error occurred while trying to call TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\EntityController->updateAction().
Error for entity.name:  This field must contain at least 3 characters.
', $response->getContent());
	}

	/**
	 * @test
	 */
	public function validationIsEnforcedForChildObjects() {
		$entity = new TestEntity();
		$entity->setName('Some Name');
		$this->testEntityRepository->add($entity);

		$subEntity = new SubEntity();
		$subEntity->setContent('Sub Entity');
		$entity->addSubEntity($subEntity);
		$this->persistenceManager->add($subEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
		$subEntityIdentifier = $this->persistenceManager->getIdentifierByObject($subEntity);

		$invalidArguments = array('entity' => array('__identity' => $entityIdentifier, 'name' => 'long enough name', 'subEntities' => array(array('__identity' => $subEntityIdentifier, 'content' => ''))));
		$response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $invalidArguments);
		$this->assertSame('An error occurred while trying to call TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\EntityController->updateAction().
Error for entity.subEntities.0.content:  This property is required.
', $response->getContent());
	}

	/**
	 * @test
	 */
	public function validationIsEnforcedForParentObject() {
		$entity = new TestEntity();
		$entity->setName('Some Name');
		$this->testEntityRepository->add($entity);

		$subEntity = new SubEntity();
		$subEntity->setContent('Sub Entity');
		$subEntity->setParentEntity($entity);
		$entity->addSubEntity($subEntity);
		$this->persistenceManager->add($subEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
		$subEntityIdentifier = $this->persistenceManager->getIdentifierByObject($subEntity);

		$invalidArguments = array(
			'entity' => array(
				'__identity' => $entityIdentifier,
				'name' => 'xx',
				'subEntities' => array(array(
					'__identity' => $subEntityIdentifier,
					'content' => 'some valid content'
				))
			)
		);
		$response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $invalidArguments);
		$this->assertSame('An error occurred while trying to call TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\EntityController->updateAction().
Error for entity.name:  This field must contain at least 3 characters.
', $response->getContent());
	}
}
?>