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

require_once(__DIR__ . '/../Fixture/Model/Entity2.php');
require_once(__DIR__ . '/../Fixture/Model/Entity3.php');
require_once(__DIR__ . '/../Fixture/Model/DirtyEntity.php');
require_once(__DIR__ . '/../Fixture/Model/CleanEntity.php');

/**
 * Testcase for the Persistence Manager
 *
 */
class PersistenceManagerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function initializeInitializesBackendWithBackendOptions()
    {
        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('initialize')->with(array('Foo' => 'Bar'));

        $manager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
        $manager->injectBackend($mockBackend);

        $manager->injectSettings(array('persistence' => array('backendOptions' => array('Foo' => 'Bar'))));
        $manager->initialize();
    }

    /**
     * @test
     */
    public function persistAllPassesAddedObjectsToBackend()
    {
        $entity2 = new \TYPO3\Flow\Tests\Persistence\Fixture\Model\Entity2();
        $objectStorage = new \SplObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

        $manager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
        $manager->injectBackend($mockBackend);
        $manager->add($entity2);

        $manager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllPassesRemovedObjectsToBackend()
    {
        $entity2 = new \TYPO3\Flow\Tests\Persistence\Fixture\Model\Entity2();
        $objectStorage = new \SplObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('setDeletedEntities')->with($objectStorage);

        $manager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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

        $mockSession = $this->getMock(\TYPO3\Flow\Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

        $manager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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

        $mockSession = $this->getMock(\TYPO3\Flow\Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(true));
        $mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->will($this->returnValue($object));

        $manager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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

        $mockSession = $this->getMock(\TYPO3\Flow\Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue(array()));

        $mockDataMapper = $this->getMock(\TYPO3\Flow\Persistence\Generic\DataMapper::class);
        $mockDataMapper->expects($this->once())->method('mapToObject')->will($this->returnValue($object));

        $manager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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

        $mockSession = $this->getMock(\TYPO3\Flow\Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $manager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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
        $persistenceManager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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

        $persistenceManager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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
        $object1 = new \ArrayObject(array('val' => '1'));
        $object2 = new \ArrayObject(array('val' => '2'));
        $object3 = new \ArrayObject(array('val' => '3'));

        $persistenceManager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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
        $object = new \ArrayObject(array('val' => '1'));
        $persistenceManager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
        $persistenceManager->remove($object);

        $this->assertAttributeContains($object, 'removedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function updateSchedulesAnObjectForPersistence()
    {
        $object = new \ArrayObject(array('val' => '1'));
        $persistenceManager = $this->getMock(\TYPO3\Flow\Persistence\Generic\PersistenceManager::class, array('isNewObject'));
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
        $mockObject = $this->getMock(\TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface::class);
        $mockObject->Persistence_Object_Identifier = 'abcdefg';

        $mockSession = $this->getMock(\TYPO3\Flow\Persistence\Generic\Session::class);
        $mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(false));
        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->any())->method('getObjectDataByIdentifier')->will($this->returnValue(false));

        $persistenceManager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
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
    public function tearDownWithBackendNotSupportingTearDownDoesNothing()
    {
        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class);
        $mockBackend->expects($this->never())->method('tearDown');

        $persistenceManager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
        $persistenceManager->injectBackend($mockBackend);

        $persistenceManager->tearDown();
    }

    /**
     * @test
     */
    public function tearDownWithBackendSupportingTearDownDelegatesCallToBackend()
    {
        $methods = array_merge(get_class_methods(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class), array('tearDown'));
        $mockBackend = $this->getMock(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface::class, $methods);
        $mockBackend->expects($this->once())->method('tearDown');

        $persistenceManager = new \TYPO3\Flow\Persistence\Generic\PersistenceManager();
        $persistenceManager->injectBackend($mockBackend);

        $persistenceManager->tearDown();
    }
}
