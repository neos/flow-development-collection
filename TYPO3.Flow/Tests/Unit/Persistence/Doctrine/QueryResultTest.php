<?php
namespace TYPO3\FLOW3\Tests\Unit\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
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
 * Testcase for \TYPO3\FLOW3\Persistence\QueryResult
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class QueryResultTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Persistence\Doctrine\QueryResult
	 */
	protected $queryResult;

	/**
	 * @var \TYPO3\FLOW3\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->query = $this->getMockBuilder('TYPO3\FLOW3\Persistence\Doctrine\Query')->disableOriginalConstructor()->disableOriginalClone()->getMock();
		$this->queryResult = new \TYPO3\FLOW3\Persistence\Doctrine\QueryResult($this->query);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertInstanceOf('TYPO3\FLOW3\Persistence\QueryInterface', $this->queryResult->getQuery());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsAClone() {
		$this->assertNotSame($this->query, $this->queryResult->getQuery());
	}
}
?>