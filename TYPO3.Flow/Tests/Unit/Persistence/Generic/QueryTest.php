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

use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Persistence;

/**
 * Testcase for \TYPO3\Flow\Persistence\Query
 */
class QueryTest extends UnitTestCase
{
    /**
     * @var Persistence\Generic\Query
     */
    protected $query;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Sets up this test case
     *
     */
    public function setUp()
    {
        $this->reflectionService = $this->createMock(ReflectionService::class);
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->query = new Persistence\Generic\Query('someType', $this->reflectionService);
        $this->query->injectObjectManager($this->objectManager);
    }

    /**
     * @test
     */
    public function executeReturnsQueryResultInstance()
    {
        $result = $this->query->execute();
        $this->assertInstanceOf(Persistence\Generic\QueryResult::class, $result);
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
