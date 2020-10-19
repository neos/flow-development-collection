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
use Neos\Utility\ObjectAccess;

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
        $mockBackend->expects(self::once())->method('setAggregateRootObjects')->with($objectStorage);

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
        $mockBackend->expects(self::once())->method('setDeletedEntities')->with($objectStorage);

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
        $mockSession->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue($fakeUuid));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);

        self::assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable()
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects(self::once())->method('hasIdentifier')->with($fakeUuid)->will(self::returnValue(true));
        $mockSession->expects(self::once())->method('getObjectByIdentifier')->with($fakeUuid)->will(self::returnValue($object));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);

        self::assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromPersistenceIfAvailable()
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects(self::once())->method('hasIdentifier')->with($fakeUuid)->will(self::returnValue(false));

        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects(self::once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will(self::returnValue([]));

        $mockDataMapper = $this->createMock(Generic\DataMapper::class);
        $mockDataMapper->expects(self::once())->method('mapToObject')->will(self::returnValue($object));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);
        $manager->injectBackend($mockBackend);
        $manager->injectDataMapper($mockDataMapper);

        self::assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsNullForUnknownObject()
    {
        $fakeUuid = 'fakeUuid';

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects(self::once())->method('hasIdentifier')->with($fakeUuid)->will(self::returnValue(false));

        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects(self::once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will(self::returnValue(false));

        $manager = new Generic\PersistenceManager();
        $manager->injectPersistenceSession($mockSession);
        $manager->injectBackend($mockBackend);

        self::assertNull($manager->getObjectByIdentifier($fakeUuid));
    }

    /**
     * @test
     */
    public function addActuallyAddsAnObjectToTheInternalObjectsArray()
    {
        $someObject = new \stdClass();
        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->add($someObject);

        self::assertContains($someObject, ObjectAccess::getProperty($persistenceManager, 'addedObjects', true));
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

        self::assertContains($object1, ObjectAccess::getProperty($persistenceManager, 'addedObjects', true));
        self::assertNotContains($object2, ObjectAccess::getProperty($persistenceManager, 'addedObjects', true));
        self::assertContains($object3, ObjectAccess::getProperty($persistenceManager, 'addedObjects', true));
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

        self::assertContains($object1, ObjectAccess::getProperty($persistenceManager, 'addedObjects', true));
        self::assertNotContains($object2, ObjectAccess::getProperty($persistenceManager, 'addedObjects', true));
        self::assertContains($object3, ObjectAccess::getProperty($persistenceManager, 'addedObjects', true));
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

        self::assertContains($object, ObjectAccess::getProperty($persistenceManager, 'removedObjects', true));
    }

    /**
     * @test
     */
    public function updateSchedulesAnObjectForPersistence()
    {
        $object = new \ArrayObject(['val' => '1']);
        $persistenceManager = $this->getMockBuilder(\Neos\Flow\Persistence\Generic\PersistenceManager::class)->setMethods(['isNewObject'])->getMock();
        $persistenceManager->expects(self::any())->method('isNewObject')->willReturn(false);

        self::assertNotContains($object, ObjectAccess::getProperty($persistenceManager, 'changedObjects', true));
        $persistenceManager->update($object);
        self::assertContains($object, ObjectAccess::getProperty($persistenceManager, 'changedObjects', true));
    }

    /**
     * @test
     */
    public function clearStateForgetsAboutNewObjects()
    {
        $mockObject = $this->createMock(PersistenceMagicInterface::class);
        $mockObject->Persistence_Object_Identifier = 'abcdefg';

        $mockSession = $this->createMock(Generic\Session::class);
        $mockSession->expects(self::any())->method('hasIdentifier')->will(self::returnValue(false));
        $mockBackend = $this->createMock(Generic\Backend\BackendInterface::class);
        $mockBackend->expects(self::any())->method('getObjectDataByIdentifier')->will(self::returnValue(false));

        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->injectPersistenceSession($mockSession);
        $persistenceManager->injectBackend($mockBackend);

        $persistenceManager->registerNewObject($mockObject);
        $persistenceManager->clearState();

        $object = $persistenceManager->getObjectByIdentifier('abcdefg');
        self::assertNull($object);
    }

    /**
     * @test
     */
    public function tearDownWithBackendSupportingTearDownDelegatesCallToBackend()
    {
        $methods = array_merge(get_class_methods(Generic\Backend\BackendInterface::class), ['tearDown']);
        $mockBackend = $this->getMockBuilder(Generic\Backend\BackendInterface::class)->setMethods($methods)->getMock();
        $mockBackend->expects(self::once())->method('tearDown');

        $persistenceManager = new Generic\PersistenceManager();
        $persistenceManager->injectBackend($mockBackend);

        $persistenceManager->tearDown();
    }
}
