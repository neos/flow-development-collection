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

use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository;

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
	 * @var TestEntityRepository
	 */
	protected $testEntityRepository;

	/**
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->testEntityRepository = new TestEntityRepository();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function entitiesArePersistedAndReconstituted() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$testEntity = $this->testEntityRepository->findAll()->getFirst();
		$this->assertEquals('FLOW3', $testEntity->getName());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function executingAQueryWillOnlyExecuteItLazily() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();
		$this->assertInstanceOf('TYPO3\FLOW3\Persistence\Doctrine\QueryResult', $allResults);
		$this->assertAttributeInternalType('null', 'rows', $allResults, 'Query Result did not load the result collection lazily.');

		$allResultsArray = $allResults->toArray();
		$this->assertEquals('FLOW3', $allResultsArray[0]->getName());
		$this->assertAttributeInternalType('array', 'rows', $allResults);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function serializingAQueryResultWillResetCachedResult() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();

		$unserializedResults = unserialize(serialize($allResults));
		$this->assertAttributeInternalType('null', 'rows', $unserializedResults, 'Query Result did not flush the result collection after serialization.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function resultCanStillBeTraversedAfterSerialization() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();
		$this->assertEquals(1, count($allResults->toArray()), 'Not correct number of entities found before running test.');

		$unserializedResults = unserialize(serialize($allResults));
		$this->assertEquals(1, count($unserializedResults->toArray()));
		$this->assertEquals('FLOW3', $unserializedResults[0]->getName());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getFirstShouldNotHaveSideEffects() {
		$this->removeExampleEntities();
		$this->insertExampleEntity('FLOW3');
		$this->insertExampleEntity('TYPO3');

		$allResults = $this->testEntityRepository->findAll();
		$this->assertEquals('FLOW3', $allResults->getFirst()->getName());

		$numberOfTotalResults = count($allResults->toArray());
		$this->assertEquals(2, $numberOfTotalResults);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aClonedEntityWillGetANewIdentifier() {
		$testEntity = new TestEntity();
		$firstIdentifier = $this->persistenceManager->getIdentifierByObject($testEntity);

		$clonedEntity = clone $testEntity;
		$secondIdentifier = $this->persistenceManager->getIdentifierByObject($clonedEntity);
		$this->assertNotEquals($firstIdentifier, $secondIdentifier);
	}

	/**
	 * Helper which inserts example data into the database.
	 *
	 * @param string $name
	 */
	protected function insertExampleEntity($name = 'FLOW3') {
		$testEntity = new TestEntity;
		$testEntity->setName($name);
		$this->testEntityRepository->add($testEntity);

		// FIXME this was tearDownPersistence(), which would reset objects in memory to a pristine state as well
		$this->persistenceManager->persistAll();
	}

	/**
	 * Remove all example entities to enforce a clean state
	 */
	protected function removeExampleEntities() {
		$this->testEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
	}
}
?>