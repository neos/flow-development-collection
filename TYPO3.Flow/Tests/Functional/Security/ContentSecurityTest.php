<?php
namespace TYPO3\Flow\Tests\Functional\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for content security
 *
 */
class ContentSecurityTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var Fixtures\RestrictableEntityRepository
	 */
	protected $restrictableEntityRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->restrictableEntityRepository = new Fixtures\RestrictableEntityRepository();
	}

	/**
	 * @test
	 */
	public function administratorsAreAllowedToSeeHiddenRestrictableEntities() {
		$this->authenticateRoles(array('TYPO3.Flow:Administrator'));

		$defaultEntity = new Fixtures\RestrictableEntity('default');
		$hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
		$hiddenEntity->setHidden(TRUE);

		$this->restrictableEntityRepository->add($defaultEntity);
		$defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
		$this->restrictableEntityRepository->add($hiddenEntity);
		$hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$result = $this->restrictableEntityRepository->findAll();
		$this->assertEquals(2, count($result));

		$this->assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));
		$this->assertNotNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));

		$this->restrictableEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}

	/**
	 * @test
	 */
	public function customersAreNotAllowedToSeeHiddenRestrictableEntities() {
		$this->authenticateRoles(array('TYPO3.Flow:Customer'));

		$defaultEntity = new Fixtures\RestrictableEntity('default');
		$hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
		$hiddenEntity->setHidden(TRUE);

		$this->restrictableEntityRepository->add($defaultEntity);
		$defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
		$this->restrictableEntityRepository->add($hiddenEntity);
		$hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$result = $this->restrictableEntityRepository->findAll();
		$this->assertEquals(1, count($result));

		$this->assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));
		$this->assertNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));

		$this->restrictableEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}

	/**
	 * @test
	 */
	public function anonymousUsersAreNotAllowedToSeeRestrictableEntitiesAtAll() {
		$defaultEntity = new Fixtures\RestrictableEntity('default');
		$hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
		$hiddenEntity->setHidden(TRUE);

		$this->restrictableEntityRepository->add($defaultEntity);
		$defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
		$this->restrictableEntityRepository->add($hiddenEntity);
		$hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$request = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($this->securityContext, 'request', TRUE);
		$this->securityContext->clearContext();
		$this->securityContext->setRequest($request);

		$result = $this->restrictableEntityRepository->findAll();
		$this->assertTrue(count($result) === 0);

		$this->assertNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));
		$this->assertNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));

		$this->restrictableEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}

	/**
	 * @test
	 */
	public function customersCannotSeeOthersRestrictableEntites() {
		$ownAccount = $this->authenticateRoles(array('TYPO3.Flow:Customer'));
		$ownAccount->setAccountIdentifier('ownAccount');
		$ownAccount->setAuthenticationProviderName('SomeProvider');
		$ownAccount->setCredentialsSource('foobar');
		$otherAccount = new \TYPO3\Flow\Security\Account();
		$otherAccount->setAccountIdentifier('othersAccount');
		$otherAccount->setAuthenticationProviderName('SomeProvider');
		$otherAccount->setCredentialsSource('foobar');
		$this->persistenceManager->add($ownAccount);
		$this->persistenceManager->add($otherAccount);

		$ownEntity = new Fixtures\RestrictableEntity('ownEntity');
		$ownEntity->setOwnerAccount($ownAccount);
		$othersEntity = new Fixtures\RestrictableEntity('othersEntity');
		$othersEntity->setOwnerAccount($otherAccount);

		$this->restrictableEntityRepository->add($ownEntity);
		$ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
		$this->restrictableEntityRepository->add($othersEntity);
		$othersEntityIdentifier = $this->persistenceManager->getIdentifierByObject($othersEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$result = $this->restrictableEntityRepository->findAll();
		$this->assertTrue(count($result) === 1);

		$this->assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));
		$this->assertNull($this->persistenceManager->getObjectByIdentifier($othersEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));

		$this->restrictableEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}

	/**
	 * @test
	 */
	public function administratorsCanSeeOthersRestrictableEntites() {
		$ownAccount = $this->authenticateRoles(array('TYPO3.Flow:Administrator', 'TYPO3.Flow:Customer'));
		$ownAccount->setAccountIdentifier('ownAccount');
		$ownAccount->setAuthenticationProviderName('SomeProvider');
		$ownAccount->setCredentialsSource('foobar');
		$otherAccount = new \TYPO3\Flow\Security\Account();
		$otherAccount->setAccountIdentifier('othersAccount');
		$otherAccount->setAuthenticationProviderName('SomeProvider');
		$otherAccount->setCredentialsSource('foobar');
		$this->persistenceManager->add($ownAccount);
		$this->persistenceManager->add($otherAccount);

		$ownEntity = new Fixtures\RestrictableEntity('ownEntity');
		$ownEntity->setOwnerAccount($ownAccount);
		$othersEntity = new Fixtures\RestrictableEntity('othersEntity');
		$othersEntity->setOwnerAccount($otherAccount);

		$this->restrictableEntityRepository->add($ownEntity);
		$ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
		$this->restrictableEntityRepository->add($othersEntity);
		$othersEntityIdentifier = $this->persistenceManager->getIdentifierByObject($othersEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$result = $this->restrictableEntityRepository->findAll();
		$this->assertTrue(count($result) === 2);

		$this->assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));
		$this->assertNotNull($this->persistenceManager->getObjectByIdentifier($othersEntityIdentifier, 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'));

		$this->restrictableEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}

}
?>