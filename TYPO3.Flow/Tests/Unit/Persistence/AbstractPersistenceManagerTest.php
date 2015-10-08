<?php
namespace TYPO3\Flow\Tests\Unit\Persistence;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the Abstract Persistence Manager
 *
 */
class AbstractPersistenceManagerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Persistence\AbstractPersistenceManager
     */
    protected $abstractPersistenceManager;

    public function setUp()
    {
        $this->abstractPersistenceManager = $this->getMock(\TYPO3\Flow\Persistence\AbstractPersistenceManager::class, array('initialize', 'persistAll', 'isNewObject', 'getObjectByIdentifier', 'createQueryForType', 'add', 'remove', 'update', 'getIdentifierByObject', 'clearState', 'isConnected'));
    }

    /**
     * @test
     */
    public function convertObjectToIdentityArrayConvertsAnObject()
    {
        $someObject = new \stdClass();
        $this->abstractPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($someObject)->will($this->returnValue(123));

        $expectedResult = array('__identity' => 123);
        $actualResult = $this->abstractPersistenceManager->convertObjectToIdentityArray($someObject);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Persistence\Exception\UnknownObjectException
     */
    public function convertObjectToIdentityArrayThrowsExceptionIfIdentityForTheGivenObjectCantBeDetermined()
    {
        $someObject = new \stdClass();
        $this->abstractPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($someObject)->will($this->returnValue(null));

        $this->abstractPersistenceManager->convertObjectToIdentityArray($someObject);
    }

    /**
     * @test
     */
    public function convertObjectsToIdentityArraysRecursivelyConvertsObjects()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $this->abstractPersistenceManager->expects($this->at(0))->method('getIdentifierByObject')->with($object1)->will($this->returnValue('identifier1'));
        $this->abstractPersistenceManager->expects($this->at(1))->method('getIdentifierByObject')->with($object2)->will($this->returnValue('identifier2'));

        $originalArray = array('foo' => 'bar', 'object1' => $object1, 'baz' => array('object2' => $object2));
        $expectedResult = array('foo' => 'bar', 'object1' => array('__identity' => 'identifier1'), 'baz' => array('object2' => array('__identity' => 'identifier2')));

        $actualResult = $this->abstractPersistenceManager->convertObjectsToIdentityArrays($originalArray);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertObjectsToIdentityArraysConvertsObjectsInIterators()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $this->abstractPersistenceManager->expects($this->at(0))->method('getIdentifierByObject')->with($object1)->will($this->returnValue('identifier1'));
        $this->abstractPersistenceManager->expects($this->at(1))->method('getIdentifierByObject')->with($object2)->will($this->returnValue('identifier2'));

        $originalArray = array('foo' => 'bar', 'object1' => $object1, 'baz' => new \ArrayObject(array('object2' => $object2)));
        $expectedResult = array('foo' => 'bar', 'object1' => array('__identity' => 'identifier1'), 'baz' => array('object2' => array('__identity' => 'identifier2')));

        $actualResult = $this->abstractPersistenceManager->convertObjectsToIdentityArrays($originalArray);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
