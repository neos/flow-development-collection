<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Generic;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for \TYPO3\Flow\Persistence\Query
 *
 */
class QueryTest extends \TYPO3\Flow\Tests\UnitTestCase
{
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
    public function setUp()
    {
        $this->reflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $this->objectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $this->query = new \TYPO3\Flow\Persistence\Generic\Query('someType', $this->reflectionService);
        $this->query->injectObjectManager($this->objectManager);
    }

    /**
     * @test
     */
    public function executeReturnsQueryResultInstance()
    {
        $result = $this->query->execute();
        $this->assertInstanceOf(\TYPO3\Flow\Persistence\Generic\QueryResult::class, $result);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setLimitAcceptsOnlyIntegers()
    {
        $this->query->setLimit(1.5);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setLimitRejectsIntegersLessThanOne()
    {
        $this->query->setLimit(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setOffsetAcceptsOnlyIntegers()
    {
        $this->query->setOffset(1.5);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setOffsetRejectsIntegersLessThanZero()
    {
        $this->query->setOffset(-1);
    }
}
