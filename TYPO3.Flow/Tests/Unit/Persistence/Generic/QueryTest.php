<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Generic;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for \TYPO3\Flow\Persistence\Query
 *
 */
class QueryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Query
	 */
	protected $query;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Sets up this test case
	 *
	 */
	public function setUp() {
		$this->reflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$this->objectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$this->query = new \TYPO3\Flow\Persistence\Generic\Query('someType', $this->reflectionService);
		$this->query->injectObjectManager($this->objectManager);
	}

	/**
	 * @test
	 */
	public function executeReturnsQueryResultInstance() {
		$result = $this->query->execute();
		$this->assertInstanceOf('TYPO3\Flow\Persistence\Generic\QueryResult', $result);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setLimitAcceptsOnlyIntegers() {
		$this->query->setLimit(1.5);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setLimitRejectsIntegersLessThanOne() {
		$this->query->setLimit(0);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setOffsetAcceptsOnlyIntegers() {
		$this->query->setOffset(1.5);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setOffsetRejectsIntegersLessThanZero() {
		$this->query->setOffset(-1);
	}
}

?>