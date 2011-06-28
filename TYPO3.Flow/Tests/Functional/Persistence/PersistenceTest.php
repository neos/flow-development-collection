<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for persistence
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PersistenceTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository
	 */
	protected $testEntityRepository;

	/**
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setUp() {
		parent::setUp();
		$this->testEntityRepository = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function entitiesArePersistedAndReconstituted() {
		$testEntity = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity->setName('FLOW3');
		$this->testEntityRepository->add($testEntity);

			// FIXME this was tearDownPersistence(), which would reset objects in memory to a pristine state as well
		$this->persistenceManager->persistAll();

		$testEntity = $this->testEntityRepository->findAll()->getFirst();
		$this->assertEquals('FLOW3', $testEntity->getName());
	}

}
?>