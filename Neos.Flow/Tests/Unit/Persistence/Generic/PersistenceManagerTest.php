<?php
namespace Neos\Flow\Tests\Unit\Persistence\Generic;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../Fixture/Model/Entity2.php');
require_once(__DIR__ . '/../Fixture/Model/Entity3.php');
require_once(__DIR__ . '/../Fixture/Model/DirtyEntity.php');
require_once(__DIR__ . '/../Fixture/Model/CleanEntity.php');

use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\Generic;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Tests\Persistence\Fixture;

/**
 * Testcase for the Persistence Manager
 */
class PersistenceManagerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function persistAllPassesAddedObjectsToBackend()
    {
        $entity2 = new Fixture\Model\Entity2();
        $objectStorage = new \SplObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

        $manager = new Generic\PersistenceManager();
        $manager->injectBackend($mockBackend);
        $manager->add($entity2);

        $manager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllPassesRemovedObjectsToBackend()
    {
        $entity2 = new Fixture\Model\Entity2();
        $objectStorage = new \SplObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('setDeletedEntities')->with($objectStorage);

        $manager = new Generic\PersistenceManager();
        $manager->injectBackend($mockBackend);
        $manager->remove($entity2);

        $manager->persistAll();
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsIdentifierFromSession()
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);

        $this->assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable()
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(true));
        $mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->will($this->returnValue($object));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);

        $this->assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromPersistenceIfAvailable()
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue([]));

        $mockDataMapper = $this->createMock(Generic\DataMapper::class);
        $mockDataMapper->expects($this->once())->method('mapToObject')->will($this->returnValue($object));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);
        $manager->injectBackend($mockBackend);
        $manager->injectDataMapper($mockDataMapper);

        $this->assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsNullForUnknownObject()
    {
        $fakeUuid = 'fakeUuid';

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);
        $manager->injectBackend($mockBackend);

        $this->assertNull($manager->getObjectByIdentifier($fakeUuid));
    }

    /**
     * @test
     */
    public function addActuallyAddsAnObjectToTheInternalObjectsArray()
    {
        $someObject = new \stdClass();
        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->add($someObject);

        $this->assertAttributeContains($someObject, 'addedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();

        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->add($object1);
        $persistenceManager->add($object2);
        $persistenceManager->add($object3);

        $persistenceManager->remove($object2);

        $this->assertAttributeContains($object1, 'addedObjects', $persistenceManager);
        $this->assertAttributeNotContains($object2, 'addedObjects', $persistenceManager);
        $this->assertAttributeContains($object3, 'addedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition()
    {
        $object1 = new \ArrayObject(['val' => '1']);
        $object2 = new \ArrayObject(['val' => '2']);
        $object3 = new \ArrayObject(['val' => '3']);

        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->add($object1);
        $persistenceManager->add($object2);
        $persistenceManager->add($object3);

        $object2['foo'] = 'bar';
        $object3['val'] = '2';

        $persistenceManager->remove($object2);

        $this->assertAttributeContains($object1, 'addedObjects', $persistenceManager);
        $this->assertAttributeNotContains($object2, 'addedObjects', $persistenceManager);
        $this->assertAttributeContains($object3, 'addedObjects', $persistenceManager);
    }

    /**
     * Make sure we remember the objects that are not currently add()ed
     * but might be in persistent storage.
     *
     * @test
     */
    public function removeRetainsObjectForObjectsNotInCurrentSession()
    {
        $object = new \ArrayObject(['val' => '1']);
        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->remove($object);

        $this->assertAttributeContains($object, 'removedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function updateSchedulesAnObjectForPersistence()
    {
        $object = new \ArrayObject(array('val' => '1'));
        $persistenceManager = $this->getMockBuilder(\Neos\Flow\Persistence\Generic\PersistenceManager::class)->setMethods(array('isNewObject'))->getMock();
        $persistenceManager->expects($this->any())->method('isNewObject')->willReturn(false);

        $this->assertAttributeNotContains($object, 'changedObjects', $persistenceManager);
        $persistenceManager->update($object);
        $this->assertAttributeContains($object, 'changedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function clearStateForgetsAboutNewObjects()
    {
        $mockObject = $this->createMock(PersistenceMagicInterface::class);
        $mockObject->Persistence_Object_Identifier = 'abcdefg';

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(false));
        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->any())->method('getObjectDataByIdentifier')->will($this->returnValue(false));

        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->injectPersistenceSession($mockSession);
        $persistenceManager->injectBackend($mockBackend);

        $persistenceManager->registerNewObject($mockObject);
        $persistenceManager->clearState();

        $object = $persistenceManager->getObjectByIdentifier('abcdefg');
        $this->assertNull($object);
    }

    /**
     * @test
     */
    public function tearDownWithBackendSupportingTearDownDelegatesCallToBackend()
    {
        $methods = array_merge(get_class_methods(Generic\Backend\BackendInterface::class), ['tearDown']);
        $mockBackend = $this->getMockBuilder(Generic\Backend\BackendInterface::class)->setMethods($methods)->getMock();
        $mockBackend->expects($this->once())->method('tearDown');

        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->injectBackend($mockBackend);

        $persistenceManager->tearDown();
    }
}
