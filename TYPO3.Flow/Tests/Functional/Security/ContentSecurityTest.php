<?php
namespace TYPO3\FLOW3\Tests\Functional\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
class ContentSecurityTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var RestrictableEntityRepository
	 */
	protected $restrictableEntityRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->restrictableEntityRepository = new Fixtures\RestrictableEntityRepository();
	}

	/**
	 * @test
	 */
	public function administratorsAreAllowedToSeeHiddenRestrictableEntities() {
		$this->authenticateRoles(array('Administrator'));

		$defaultEntity = new Fixtures\RestrictableEntity('default');
		$hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
		$hiddenEntity->setHidden(TRUE);

		$this->restrictableEntityRepository->add($defaultEntity);
		$this->restrictableEntityRepository->add($hiddenEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$result = $this->restrictableEntityRepository->findAll();
		$this->assertTrue(count($result) === 2);

		$this->restrictableEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}

	/**
	 * @test
	 */
	public function anonymousUsersAreNotAllowedToSeeHiddenRestrictableEntities() {
		$defaultEntity = new Fixtures\RestrictableEntity('default');
		$hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
		$hiddenEntity->setHidden(TRUE);

		$this->restrictableEntityRepository->add($defaultEntity);
		$this->restrictableEntityRepository->add($hiddenEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$result = $this->restrictableEntityRepository->findAll();
		$this->assertTrue(count($result) === 1);

		$this->restrictableEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}
}
?>