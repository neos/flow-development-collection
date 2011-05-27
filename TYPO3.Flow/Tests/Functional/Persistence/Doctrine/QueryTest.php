<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\Persistence\Doctrine;

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

use \F3\FLOW3\Persistence\Doctrine\Query;

/**
 * Testcase for query
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class QueryTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @test
	 */
	public function simpleQueryCanBeSerializedAndDeserialized() {
		$query = new Query('F3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertQueryEquals($query, $unserializedQuery);
	}

	/**
	 * @test
	 */
	public function moreComplexQueryCanBeSerializedAndDeserialized() {
		$query = new Query('F3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$query->matching($query->equals('name', 'some'));

		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertQueryEquals($query, $unserializedQuery);
	}

	/**
	 * @test
	 */
	public function moreComplexQueryCanBeExecutedAfterDeserialization() {
		$testEntityRepository = new \F3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository();

		$testEntity1 = new \F3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('FLOW3');
		$testEntityRepository->add($testEntity1);

		$testEntity2 = new \F3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity2->setName('some');
		$testEntityRepository->add($testEntity1);

		$this->persistenceManager->persistAll();

		$query = new Query('F3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$query->matching($query->equals('name', 'FLOW3'));

		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);
		$this->assertEquals(1, $unserializedQuery->execute()->count());
		$this->assertEquals(array($testEntity1), $unserializedQuery->execute()->toArray());
	}

	protected function assertQueryEquals(Query $expected, Query $actual) {
		$this->assertEquals($expected->getConstraint(), $actual->getConstraint());
		$this->assertEquals($expected->getOrderings(), $actual->getOrderings());
		$this->assertEquals($expected->getOffset(), $actual->getOffset());
		$this->assertEquals($expected->getLimit(), $actual->getLimit());
	}

}
?>