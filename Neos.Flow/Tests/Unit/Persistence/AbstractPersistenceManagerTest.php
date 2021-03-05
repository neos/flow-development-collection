<?php
namespace Neos\Flow\Tests\Unit\Persistence;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\AbstractPersistenceManager;
use Neos\Flow\Persistence\Exception\UnknownObjectException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Abstract Persistence Manager
 */
class AbstractPersistenceManagerTest extends UnitTestCase
{
    /**
     * @var AbstractPersistenceManager
     */
    protected $abstractPersistenceManager;

    protected function setUp(): void
    {
        $this->abstractPersistenceManager = $this->getMockBuilder(AbstractPersistenceManager::class)->setMethods(['initialize', 'persistAll', 'isNewObject', 'getObjectByIdentifier', 'createQueryForType', 'add', 'remove', 'update', 'getIdentifierByObject', 'clearState', 'isConnected'])->getMock();
    }

    /**
     * @test
     */
    public function convertObjectToIdentityArrayConvertsAnObject()
    {
        $someObject = new \stdClass();
        $this->abstractPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($someObject)->will(self::returnValue(123));

        $expectedResult = ['__identity' => 123];
        $actualResult = $this->abstractPersistenceManager->convertObjectToIdentityArray($someObject);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertObjectToIdentityArrayThrowsExceptionIfIdentityForTheGivenObjectCantBeDetermined()
    {
        $this->expectException(UnknownObjectException::class);
        $someObject = new \stdClass();
        $this->abstractPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($someObject)->will(self::returnValue(null));

        $this->abstractPersistenceManager->convertObjectToIdentityArray($someObject);
    }

    /**
     * @test
     */
    public function convertObjectsToIdentityArraysRecursivelyConvertsObjects()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $this->abstractPersistenceManager->expects(self::exactly(2))->method('getIdentifierByObject')
            ->withConsecutive([$object1], [$object2])->willReturnOnConsecutiveCalls('identifier1', 'identifier2');

        $originalArray = ['foo' => 'bar', 'object1' => $object1, 'baz' => ['object2' => $object2]];
        $expectedResult = ['foo' => 'bar', 'object1' => ['__identity' => 'identifier1'], 'baz' => ['object2' => ['__identity' => 'identifier2']]];

        $actualResult = $this->abstractPersistenceManager->convertObjectsToIdentityArrays($originalArray);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertObjectsToIdentityArraysConvertsObjectsInIterators()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $this->abstractPersistenceManager->expects(self::exactly(2))->method('getIdentifierByObject')
            ->withConsecutive([$object1], [$object2])->willReturnOnConsecutiveCalls('identifier1', 'identifier2');

        $originalArray = ['foo' => 'bar', 'object1' => $object1, 'baz' => new \ArrayObject(['object2' => $object2])];
        $expectedResult = ['foo' => 'bar', 'object1' => ['__identity' => 'identifier1'], 'baz' => ['object2' => ['__identity' => 'identifier2']]];

        $actualResult = $this->abstractPersistenceManager->convertObjectsToIdentityArrays($originalArray);
        self::assertEquals($expectedResult, $actualResult);
    }
}
