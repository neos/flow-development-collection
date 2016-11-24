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

use Neos\Flow\Aop\ProxyInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Persistence;

/**
 * Testcase for the Persistence Session
 */
class SessionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function objectRegisteredWithRegisterReconstitutedEntityCanBeRetrievedWithGetReconstitutedEntities()
    {
        $someObject = new \ArrayObject([]);
        $session = new Persistence\Generic\Session();
        $session->registerReconstitutedEntity($someObject, ['identifier' => 'fakeUuid']);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        $this->assertTrue($ReconstitutedEntities->contains($someObject));
    }

    /**
     * @test
     */
    public function unregisterReconstitutedEntityRemovesObjectFromSession()
    {
        $someObject = new \ArrayObject([]);
        $session = new Persistence\Generic\Session();
        $session->registerObject($someObject, 'fakeUuid');
        $session->registerReconstitutedEntity($someObject, ['identifier' => 'fakeUuid']);
        $session->unregisterReconstitutedEntity($someObject);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        $this->assertFalse($ReconstitutedEntities->contains($someObject));
    }

    /**
     * @test
     */
    public function hasObjectReturnsTrueForRegisteredObject()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $session = new Persistence\Generic\Session();
        $session->registerObject($object1, 12345);

        $this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        $this->assertFalse($session->hasObject($object2), 'Session claims it does have unregistered object.');
    }

    /**
     * @test
     */
    public function hasIdentifierReturnsTrueForRegisteredObject()
    {
        $session = new Persistence\Generic\Session();
        $session->registerObject(new \stdClass(), 12345);

        $this->assertTrue($session->hasIdentifier('12345'), 'Session claims it does not have registered object.');
        $this->assertFalse($session->hasIdentifier('67890'), 'Session claims it does have unregistered object.');
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsRegisteredUUIDForObject()
    {
        $object = new \stdClass();
        $session = new Persistence\Generic\Session();
        $session->registerObject($object, 12345);

        $this->assertEquals($session->getIdentifierByObject($object), 12345, 'Did not get UUID registered for object.');
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsRegisteredObjectForUUID()
    {
        $object = new \stdClass();
        $session = new Persistence\Generic\Session();
        $session->registerObject($object, 12345);

        $this->assertSame($session->getObjectByIdentifier('12345'), $object, 'Did not get object registered for UUID.');
    }

    /**
     * @test
     */
    public function unregisterObjectRemovesRegisteredObject()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $session = new Persistence\Generic\Session();
        $session->registerObject($object1, 12345);
        $session->registerObject($object2, 67890);

        $this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasIdentifier('12345'), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasIdentifier('67890'), 'Session claims it does not have registered object.');

        $session->unregisterObject($object1);

        $this->assertFalse($session->hasObject($object1), 'Session claims it does have unregistered object.');
        $this->assertFalse($session->hasIdentifier('12345'), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasObject($object2), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasIdentifier('67890'), 'Session claims it does not have registered object.');
    }

    /**
     * @test
     */
    public function newObjectsAreConsideredDirty()
    {
        $session = new Persistence\Generic\Session();
        $this->assertTrue($session->isDirty(new \stdClass(), 'foo'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueForUnregisteredReconstitutedEntities()
    {
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['isReconstitutedEntity'])->getMock();
        $session->expects($this->once())->method('isReconstitutedEntity')->will($this->returnValue(false));
        $this->assertTrue($session->isDirty(new \stdClass(), 'foo'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseForNullInBothCurrentAndCleanValue()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $foo; }');
        $object = new $className();

        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject'])->getMock();
        $session->registerReconstitutedEntity($object, ['identifier' => 'fakeUuid']);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

        $this->assertFalse($session->isDirty($object, 'foo'));
    }

    /**
     * @test
     */
    public function isDirtyAsksIsPropertyDirtyForChangedLiterals()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $foo; }');
        $object = new $className();
        $object->foo = 'different';

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'foo' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'bar'
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject', 'isSingleValuedPropertyDirty'])->getMock();
        $session->registerReconstitutedEntity($object, $cleanData);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
        $session->expects($this->once())->method('isSingleValuedPropertyDirty')->with('string', 'bar', 'different')->will($this->returnValue(true));

        $this->assertTrue($session->isDirty($object, 'foo'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseForUnactivatedLazyObjects()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $foo; }');
        $object = new $className();
        $object->Flow_Persistence_LazyLoadingObject_thawProperties = 'dummy';

        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['dummy'])->getMock();
        $session->registerReconstitutedEntity($object, ['identifier' => 'fakeUuid']);
        $this->assertFalse($session->isDirty($object, 'foo'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueForTraversablesWhoseCountDiffers()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $foo; }');
        $object = new $className();
        $object->foo = ['foo', 'bar', 'baz'];

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'foo' => [
                    'type' => 'string',
                    'multivalue' => true,
                    'value' => [[], []]
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject'])->getMock();
        $session->registerReconstitutedEntity($object, $cleanData);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

        $this->assertTrue($session->isDirty($object, 'foo'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueForNestedArrayWhoseCountDiffers()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $foo; }');
        $object = new $className();
        $object->foo = ['foo', ['bar', 'baz']];

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'foo' => [
                    'type' => 'string',
                    'multivalue' => true,
                    'value' => [
                        ['type' => 'string', 'index' => 0, 'value' => 'foo'],
                        [
                            'type' => 'array',
                            'index' => 1,
                            'value' => ['type' => 'string', 'index' => 0, 'value' => 'bar'],
                        ]
                    ]
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject'])->getMock();
        $session->registerReconstitutedEntity($object, $cleanData);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

        $this->assertTrue($session->isDirty($object, 'foo'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueForSplObjectStorageWhoseContainedObjectsDiffer()
    {
        $object = new \stdClass();
        $object->Persistence_Object_Identifier = 'dirtyUuid';
        $splObjectStorage = new \SplObjectStorage();
        $splObjectStorage->attach($object);
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $splObjectStorage; }');
        $parent = new $className();
        $parent->splObjectStorage = $splObjectStorage;

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'splObjectStorage' => [
                    'type' => 'SplObjectStorage',
                    'multivalue' => true,
                    'value' => [
                        [
                            'value' => ['identifier' => 'cleanUuid']
                        ]
                    ]
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject'])->getMock();
        $session->registerReconstitutedEntity($parent, $cleanData);
        $session->expects($this->atLeastOnce())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

        $this->assertTrue($session->isDirty($parent, 'splObjectStorage'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueForArraysWhoseContainedObjectsDiffer()
    {
        $object = new \stdClass();
        $object->Persistence_Object_Identifier = 'dirtyUuid';
        $array = [];
        $array[] = $object;
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $array; }');
        $parent = new $className();
        $parent->array = $array;

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'array' => [
                    'type' => 'array',
                    'multivalue' => true,
                    'value' => [
                        [
                            'type' => 'Some\Object',
                            'index' => 0,
                            'value' => ['identifier' => 'cleanUuid']
                        ]
                    ]
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject', 'isSingleValuedPropertyDirty'])->getMock();
        $session->registerReconstitutedEntity($parent, $cleanData);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
        $session->expects($this->once())->method('isSingleValuedPropertyDirty')->will($this->returnValue(true));

        $this->assertTrue($session->isDirty($parent, 'array'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseForCleanArrays()
    {
        $object = new \stdClass();
        $object->Persistence_Object_Identifier = 'cleanHash';
        $array = [];
        $array[] = $object;
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $array; }');
        $parent = new $className();
        $parent->array = $array;

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'array' => [
                    'type' => 'array',
                    'multivalue' => true,
                    'value' => [
                        [
                            'type' => 'Some\Object',
                            'index' => 0,
                            'value' => ['identifier' => 'cleanHash']
                        ]
                    ]
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject', 'isSingleValuedPropertyDirty'])->getMock();
        $session->registerReconstitutedEntity($parent, $cleanData);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
        $session->expects($this->once())->method('isSingleValuedPropertyDirty')->with('Some\Object', ['identifier' => 'cleanHash'], $object)->will($this->returnValue(false));

        $this->assertFalse($session->isDirty($parent, 'array'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseForCleanNestedArrays()
    {
        $object = new \stdClass();
        $object->Persistence_Object_Identifier = 'cleanHash';
        $array = [[$object]];
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $array; }');
        $parent = new $className();
        $parent->array = $array;

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'array' => [
                    'type' => 'array',
                    'multivalue' => true,
                    'value' => [
                        [
                            'type' => 'array',
                            'index' => 0,
                            'value' => [
                                [
                                    'type' => 'Some\Object',
                                    'index' => 0,
                                    'value' => ['identifier' => 'cleanHash']
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject', 'isSingleValuedPropertyDirty'])->getMock();
        $session->registerReconstitutedEntity($parent, $cleanData);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
        $session->expects($this->once())->method('isSingleValuedPropertyDirty')->will($this->returnValue(false));

        $this->assertFalse($session->isDirty($parent, 'array'));
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueForArraysWithNewMembers()
    {
        $object = new \stdClass();
        $object->Persistence_Object_Identifier = 'dirtyUuid';
        $array = [];
        $array[] = $object;
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $array; }');
        $parent = new $className();
        $parent->array = $array;

        $cleanData = [
            'identifier' => 'fakeUuid',
            'properties' => [
                'array' => [
                    'type' => 'array',
                    'multivalue' => true,
                    'value' => [
                        [
                            'type' => 'Some\Object',
                            'index' => 'new',
                            'value' => ['identifier' => 'cleanUuid']
                        ]
                    ]
                ]
            ]
        ];
        $session = $this->getMockBuilder(Persistence\Generic\Session::class)->setMethods(['getIdentifierByObject'])->getMock();
        $session->registerReconstitutedEntity($parent, $cleanData);
        $session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

        $this->assertTrue($session->isDirty($parent, 'array'));
    }

    /**
     * Returns tuples of the form <type, current, clean, expected> for
     * isSingleValuedPropertyDirty()
     */
    public function propertyData()
    {
        $dateTime = new \DateTime();
        $entity = new \stdClass();
        $valueObject = new \stdClass();

        return [
            ['string', 'foo', 'foo', false],
            ['string', 'foo', 'bar', true],
            ['boolean', true, true, false],
            ['boolean', true, false, true],
            ['float', 1.2, 1.2, false],
            ['float', 1.2, 1.3, true],
            ['integer', 10, 10, false],
            ['integer', 10, 12, true],
            ['Some\Entity', $entity, ['identifier' => null], false],
            ['Some\Entity', $entity, ['identifier' => 'dirtyUuid'], true],
            ['Some\ValueObject', $valueObject, ['identifier' => null], false],
            ['Some\ValueObject', $valueObject, ['identifier' => 'dirtyHash'], true],
            ['DateTime', $dateTime, $dateTime->getTimestamp(), false],
            ['DateTime', $dateTime, $dateTime->getTimestamp()+1, true],
        ];
    }

    /**
     * @test
     * @dataProvider propertyData
     */
    public function isSingleValuedPropertyDirtyWorksAsExpected($type, $current, $clean, $expected)
    {
        $session = $this->getAccessibleMock(Persistence\Generic\Session::class, ['getIdentifierByObject']);
        $this->assertEquals($session->_call('isSingleValuedPropertyDirty', $type, $clean, $current), $expected);
    }

    /**
     * @test
     */
    public function getCleanStateOfPropertyReturnsNullIfPropertyWasNotInObjectData()
    {
        $entity = new \stdClass();

        $reconstitutedEntitiesData = [
            'abc' => [
                'properties' => [
                    'foo' => ['type' => 'string']
                ]
            ]
        ];

        $session = $this->getAccessibleMock(Persistence\Generic\Session::class, ['isReconstitutedEntity', 'getIdentifierByObject']);
        $session->_set('reconstitutedEntitiesData', $reconstitutedEntitiesData);

        $session->expects($this->any())->method('isReconstitutedEntity')->with($entity)->will($this->returnValue(true));
        $session->expects($this->any())->method('getIdentifierByObject')->with($entity)->will($this->returnValue('abc'));

        $state = $session->getCleanStateOfProperty($entity, 'bar');
        $this->assertNull($state);
    }

    /**
     * @test
     */
    public function getCleanStateOfPropertyReturnsNullIfObjectWasNotReconstituted()
    {
        $entity = new \stdClass();

        $session = $this->getAccessibleMock(Persistence\Generic\Session::class, ['isReconstitutedEntity']);

        $session->expects($this->any())->method('isReconstitutedEntity')->with($entity)->will($this->returnValue(false));

        $state = $session->getCleanStateOfProperty($entity, 'bar');
        $this->assertNull($state);
    }

    /**
     * @test
     */
    public function getCleanStateOfPropertyReturnsPropertyData()
    {
        $entity = new \stdClass();

        $reconstitutedEntitiesData = [
            'abc' => [
                'properties' => [
                    'foo' => ['type' => 'string']
                ]
            ]
        ];

        $session = $this->getAccessibleMock(Persistence\Generic\Session::class, ['isReconstitutedEntity', 'getIdentifierByObject']);
        $session->_set('reconstitutedEntitiesData', $reconstitutedEntitiesData);

        $session->expects($this->any())->method('isReconstitutedEntity')->with($entity)->will($this->returnValue(true));
        $session->expects($this->any())->method('getIdentifierByObject')->with($entity)->will($this->returnValue('abc'));

        $state = $session->getCleanStateOfProperty($entity, 'foo');
        $this->assertEquals(['type' => 'string'], $state);
    }

    /**
     * Does it return the UUID for an object know to the identity map?
     *
     * @test
     */
    public function getIdentifierByObjectReturnsUUIDForKnownObject()
    {
        $knownObject = $this->createMock(ProxyInterface::class);
        $fakeUUID = '123-456';

        $session = new Persistence\Generic\Session();
        $session->registerObject($knownObject, $fakeUUID);

        $this->assertEquals($fakeUUID, $session->getIdentifierByObject($knownObject));
    }

    /**
     * Does it return the UUID for an AOP proxy not being in the identity map
     * but having Persistence_Object_Identifier?
     *
     * @test
     */
    public function getIdentifierByObjectReturnsUuidForObjectBeingAOPProxy()
    {
        $knownObject = $this->createMock(ProxyInterface::class);
        $knownObject->Persistence_Object_Identifier = 'fakeUuid';

        $session = new Persistence\Generic\Session();
        $session->injectReflectionService($this->createMock(ReflectionService::class));

        $this->assertEquals('fakeUuid', $session->getIdentifierByObject($knownObject));
    }

    /**
     * Does it return the value object hash for an AOP proxy not being in the
     * identity map but having Persistence_Object_Identifier?
     *
     * @test
     */
    public function getIdentifierByObjectReturnsHashForObjectBeingAOPProxy()
    {
        $knownObject = $this->createMock(ProxyInterface::class);
        $knownObject->Persistence_Object_Identifier = 'fakeHash';

        $session = new Persistence\Generic\Session();
        $session->injectReflectionService($this->createMock(ReflectionService::class));

        $this->assertEquals('fakeHash', $session->getIdentifierByObject($knownObject));
    }

    /**
     * Does it return NULL for an AOP proxy not being in the identity map and
     * not having Persistence_Object_Identifier?
     *
     * @test
     */
    public function getIdentifierByObjectReturnsNullForUnknownObjectBeingAOPProxy()
    {
        $unknownObject = $this->createMock(ProxyInterface::class);

        $session = new Persistence\Generic\Session();
        $session->injectReflectionService($this->createMock(ReflectionService::class));

        $this->assertNull($session->getIdentifierByObject($unknownObject));
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsValueOfPropertyTaggedWithId()
    {
        $object = $this->createMock(ProxyInterface::class);
        $object->Persistence_Object_Identifier = 'randomlyGeneratedUuid';
        $object->customId = 'customId';

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getPropertyNamesByTag')->will($this->returnValue(['customId']));

        $session = new Persistence\Generic\Session();
        $session->injectReflectionService($mockReflectionService);

        $this->assertEquals('customId', $session->getIdentifierByObject($object));
    }
}
