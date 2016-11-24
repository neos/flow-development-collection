<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\ObjectManagement\ObjectSerializer;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Annotations as Flow;

/**
 * @testcase for the object serializer
 */
class ObjectSerializerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySerializesTheCorrectPropertyArrayUnderTheCorrectObjectName()
    {
        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $privateProperty = \'privateProperty\';
			protected $protectedProperty = \'protectedProperty\';
			public $publicProperty = \'publicProperty\';
		}');

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(['privateProperty', 'protectedProperty', 'publicProperty']));

        $objectSerializer = new ObjectSerializer($this->getMockBuilder(SessionInterface::class)->disableOriginalConstructor()->getMock());
        $objectSerializer->injectReflectionService($mockReflectionService);

        $someObject = new $className();
        $expectedPropertyArray = [
          \spl_object_hash($someObject) => [
            ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'privateProperty' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 'privateProperty',
                    ],
                    'protectedProperty' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 'protectedProperty',
                    ],
                    'publicProperty' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 'publicProperty',
                    ]
                ]
          ]
        ];

        $this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The object was not serialized correctly as property array.');
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySerializesArrayPropertiesCorrectly()
    {
        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $arrayProperty = array(1,2,3);
		}');

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(['arrayProperty']));

        $objectSerializer = $this->getMockBuilder(ObjectSerializer::class)->disableOriginalConstructor()->setMethods(['buildStorageArrayForArrayProperty'])->getMock();
        $objectSerializer->injectReflectionService($mockReflectionService);

        $objectSerializer->expects($this->once())->method('buildStorageArrayForArrayProperty')->with([1, 2, 3])->will($this->returnValue('storable array'));

        $someObject = new $className();
        $expectedPropertyArray = [
            \spl_object_hash($someObject) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'arrayProperty' => [
                        ObjectSerializer::TYPE => 'array',
                        ObjectSerializer::VALUE => 'storable array',
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The array property was not serialized correctly.');
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySerializesArrayObjectPropertiesCorrectly()
    {
        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $arrayObjectProperty;

			public function __construct() {
				$this->arrayObjectProperty = new \ArrayObject(array(1,2,3));
			}
		}');

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(['arrayObjectProperty']));

        $objectSerializer = $this->getMockBuilder(ObjectSerializer::class)->disableOriginalConstructor()->setMethods(['buildStorageArrayForArrayProperty'])->getMock();
        $objectSerializer->injectReflectionService($mockReflectionService);

        $objectSerializer->expects($this->once())->method('buildStorageArrayForArrayProperty')->with([1, 2, 3])->will($this->returnValue('storable array'));

        $someObject = new $className();
        $expectedPropertyArray = [
            \spl_object_hash($someObject) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'arrayObjectProperty' => [
                        ObjectSerializer::TYPE => 'ArrayObject',
                        ObjectSerializer::VALUE => 'storable array',
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The ArrayObject property was not serialized correctly.');
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySerializesObjectPropertiesCorrectly()
    {
        $className1 = 'DummyClass1' . md5(uniqid(mt_rand(), true));
        $className2 = 'DummyClass2' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className2 . '{}');

        eval('class ' . $className1 . ' {
			private $objectProperty;

			public function __construct() {
				$this->objectProperty = new ' . $className2 . '();
			}

			public function getObjectProperty() {
				return $this->objectProperty;
			}
		}');

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className1)->will($this->returnValue(['objectProperty']));
        $mockReflectionService->expects($this->at(2))->method('getClassPropertyNames')->with($className2)->will($this->returnValue([]));

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->once())->method('getObjectNameByClassName')->with($className2)->will($this->returnValue('objectName2'));
        $mockObjectManager->expects($this->once())->method('getScope')->with('objectName2')->will($this->returnValue(Configuration::SCOPE_PROTOTYPE));

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $objectSerializer = $this->getMockBuilder(ObjectSerializer::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock();
        $objectSerializer->injectReflectionService($mockReflectionService);
        $objectSerializer->injectObjectManager($mockObjectManager);
        $objectSerializer->injectPersistenceManager($mockPersistenceManager);

        $someObject = new $className1();

        $expectedPropertyArray = [
            \spl_object_hash($someObject) => [
                ObjectSerializer::CLASSNAME => $className1,
                ObjectSerializer::PROPERTIES => [
                    'objectProperty' => [
                        ObjectSerializer::TYPE => 'object',
                        ObjectSerializer::VALUE => spl_object_hash($someObject->getObjectProperty()),
                    ]
                ]
            ],
            spl_object_hash($someObject->getObjectProperty()) => [
                ObjectSerializer::CLASSNAME => $className2,
                ObjectSerializer::PROPERTIES => [],
            ]
        ];

        $this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The object property was not serialized correctly.');
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySkipsObjectPropertiesThatAreScopeSingleton()
    {
        $propertyClassName1 = 'DummyClass' . md5(uniqid(mt_rand(), true));
        $propertyClassName2 = 'DummyClass' . md5(uniqid(mt_rand(), true));
        $propertyClassName3 = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $propertyClassName1 . ' {}');
        eval('class ' . $propertyClassName2 . ' {}');
        eval('class ' . $propertyClassName3 . ' {}');

        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $property1;
			private $property2;
			private $property3;

			public function __construct() {
				$this->property1 = new ' . $propertyClassName1 . '();
				$this->property2 = new ' . $propertyClassName2 . '();
				$this->property3 = new ' . $propertyClassName3 . '();
			}

			public function getProperty1() {
				return $this->property1;
			}

			public function getProperty3() {
				return $this->property3;
			}
		}');

        $object = new $className();

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(['property1', 'property2', 'property3']));
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue([]));

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->at(0))->method('getObjectNameByClassName')->with($propertyClassName1)->will($this->returnValue('propertyObjectName1'));
        $mockObjectManager->expects($this->at(1))->method('getScope')->with('propertyObjectName1')->will($this->returnValue(Configuration::SCOPE_PROTOTYPE));
        $mockObjectManager->expects($this->at(2))->method('getObjectNameByClassName')->with($propertyClassName2)->will($this->returnValue('propertyObjectName2'));
        $mockObjectManager->expects($this->at(3))->method('getScope')->with('propertyObjectName2')->will($this->returnValue(Configuration::SCOPE_SINGLETON));
        $mockObjectManager->expects($this->at(4))->method('getObjectNameByClassName')->with($propertyClassName3)->will($this->returnValue('propertyObjectName3'));
        $mockObjectManager->expects($this->at(5))->method('getScope')->with('propertyObjectName3')->will($this->returnValue(Configuration::SCOPE_SESSION));

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['dummy'], [], '', true);
        $objectSerializer->injectReflectionService($mockReflectionService);
        $objectSerializer->injectObjectManager($mockObjectManager);
        $objectSerializer->injectPersistenceManager($mockPersistenceManager);
        $objectSerializer->_set('objects', [$className => $object]);

        $objectHash1 = spl_object_hash($object->getProperty1());
        $objectHash3 = spl_object_hash($object->getProperty3());
        $expectedArray = [
            spl_object_hash($object) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'property1' => [
                        ObjectSerializer::TYPE => 'object',
                        ObjectSerializer::VALUE => $objectHash1,
                    ],
                    'property3' => [
                        ObjectSerializer::TYPE => 'object',
                        ObjectSerializer::VALUE => $objectHash3,
                    ]
                ]
            ],
            $objectHash1 => [
                ObjectSerializer::CLASSNAME => $propertyClassName1,
                ObjectSerializer::PROPERTIES => [],
            ],
            $objectHash3 => [
                ObjectSerializer::CLASSNAME => $propertyClassName3,
                ObjectSerializer::PROPERTIES => [],
            ]
        ];

        $this->assertEquals($expectedArray, $objectSerializer->serializeObjectAsPropertyArray($object), 'The singleton has not been skipped.');
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySkipsPropertiesThatAreAnnotatedToBeTransient()
    {
        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $privateProperty = \'privateProperty\';
			protected $protectedProperty = \'protectedProperty\';
			public $publicProperty = \'publicProperty\';
		}');

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(['privateProperty', 'protectedProperty', 'publicProperty']));
        $mockReflectionService->expects($this->at(1))->method('isPropertyTaggedWith')->with($className, 'privateProperty', 'transient')->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(2))->method('isPropertyTaggedWith')->with($className, 'protectedProperty', 'transient')->will($this->returnValue(true));
        $mockReflectionService->expects($this->at(3))->method('isPropertyTaggedWith')->with($className, 'publicProperty', 'transient')->will($this->returnValue(false));

        /** @var ObjectSerializer $objectSerializer */
        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['dummy'], [], '', false);
        $objectSerializer->injectReflectionService($mockReflectionService);

        $someObject = new $className();
        $expectedPropertyArray = [
            spl_object_hash($someObject) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'privateProperty' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 'privateProperty',
                    ],
                    'publicProperty' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 'publicProperty',
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The object was not stored correctly as property array.');
    }

    /**
     * @test
     */
    public function serializeObjectSerializesObjectInstancesOnlyOnceToPreventRecursion()
    {
        $className = 'DummyClassForRecursion' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			public $name;
			public $parent;
			public $child;
		}');

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getObjectNameByClassName')->with($className)->will($this->returnValue(ObjectSerializer::CLASSNAME));

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(['name', 'parent', 'child']));
        $mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->will($this->returnValue(false));

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        /** @var ObjectSerializer $objectSerializer */
        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['dummy']);
        $objectSerializer->injectObjectManager($mockObjectManager);
        $objectSerializer->injectReflectionService($mockReflectionService);
        $objectSerializer->injectPersistenceManager($mockPersistenceManager);

        $objectA = new $className();
        $objectA->name = 'A';
        $objectB = new $className();
        $objectB->name = 'B';

        $objectA->child = $objectB;
        $objectB->parent = $objectA;

        $expectedPropertyArray = [
            spl_object_hash($objectB) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'name' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 'B',
                    ],
                    'parent' => [
                        ObjectSerializer::TYPE => 'object',
                        ObjectSerializer::VALUE => \spl_object_hash($objectA),
                    ],
                    'child' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => null,
                    ]
                ]
            ],
            spl_object_hash($objectA) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'name' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 'A',
                    ],
                    'parent' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => null,
                    ],
                    'child' => [
                        ObjectSerializer::TYPE => 'object',
                        ObjectSerializer::VALUE => \spl_object_hash($objectB),
                    ]
                ]
            ]
        ];

        $actualPropertyArray = $objectSerializer->serializeObjectAsPropertyArray($objectA);
        $this->assertEquals($expectedPropertyArray, $actualPropertyArray);
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySerializesOnlyTheUuidOfEntityObjectsIfTheyAreNotMarkedAsNew()
    {
        $sessionClassName = 'dummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $sessionClassName . ' {
            public $entityProperty;
        }');

        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \\' . \Neos\Flow\ObjectManagement\Proxy\ProxyInterface::class .' {
            public function Flow_Aop_Proxy_invokeJoinPoint(\\' . \Neos\Flow\Aop\JoinPointInterface::class . ' $joinPoint) {}
            public function __clone() {}
            public function __wakeup() {}
        }');

        $entityObject = new $entityClassName();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('isNewObject')->with($entityObject)->will($this->returnValue(false));
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($entityObject)->will($this->returnValue('someUUID'));

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($sessionClassName)->will($this->returnValue(['entityProperty']));
        $mockReflectionService->expects($this->at(2))->method('isClassAnnotatedWith')->with($entityClassName, \Neos\Flow\Annotations\Entity::class)->will($this->returnValue(true));

        /** @var ObjectSerializer $objectSerializer */
        $objectSerializer = $this->getMockBuilder(ObjectSerializer::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock();
        $objectSerializer->injectReflectionService($mockReflectionService);
        $objectSerializer->injectPersistenceManager($mockPersistenceManager);

        $expectedArray = [
            ObjectSerializer::CLASSNAME => $sessionClassName,
            ObjectSerializer::PROPERTIES => [
                'entityProperty' => [
                    ObjectSerializer::TYPE => 'persistenceObject',
                    ObjectSerializer::VALUE => $entityClassName . ':' . 'someUUID',
                ]
            ]
        ];

        $sessionObject = new $sessionClassName();
        $sessionObject->entityProperty = $entityObject;

        $objectsAsArray = $objectSerializer->serializeObjectAsPropertyArray($sessionObject);
        $this->assertEquals($expectedArray, $objectsAsArray[spl_object_hash($sessionObject)]);
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArraySerializessOnlyTheUuidOfPersistenceValueobjectsIfTheyAreNotMarkedAsNew()
    {
        $sessionClassName = 'dummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $sessionClassName . ' {
            public $entityProperty;
        }');

        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \\' . \Neos\Flow\ObjectManagement\Proxy\ProxyInterface::class .' {
            public function Flow_Aop_Proxy_invokeJoinPoint(\\' . \Neos\Flow\Aop\JoinPointInterface::class . ' $joinPoint) {}
            public function __clone() {}
            public function __wakeup() {}
        }');

        $entityObject = new $entityClassName();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('isNewObject')->with($entityObject)->will($this->returnValue(false));
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($entityObject)->will($this->returnValue('someUUID'));

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($sessionClassName)->will($this->returnValue(['entityProperty']));
        $mockReflectionService->expects($this->at(2))->method('isClassAnnotatedWith')->with($entityClassName, \Neos\Flow\Annotations\Entity::class)->will($this->returnValue(false));
        $mockReflectionService->expects($this->at(3))->method('isClassAnnotatedWith')->with($entityClassName, \Neos\Flow\Annotations\ValueObject::class)->will($this->returnValue(true));

        /** @var ObjectSerializer $objectSerializer */
        $objectSerializer = $this->getMockBuilder(ObjectSerializer::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock();
        $objectSerializer->injectReflectionService($mockReflectionService);
        $objectSerializer->injectPersistenceManager($mockPersistenceManager);

        $expectedArray = [
            ObjectSerializer::CLASSNAME => $sessionClassName,
            ObjectSerializer::PROPERTIES => [
                'entityProperty' => [
                    ObjectSerializer::TYPE => 'persistenceObject',
                    ObjectSerializer::VALUE => $entityClassName . ':someUUID'
                ]
            ]
        ];

        $sessionObject = new $sessionClassName();
        $sessionObject->entityProperty = $entityObject;

        $objectsAsArray = $objectSerializer->serializeObjectAsPropertyArray($sessionObject);
        $this->assertEquals($expectedArray, $objectsAsArray[spl_object_hash($sessionObject)]);
    }

    /**
     * @test
     */
    public function deserializeObjectsArraySetsTheInternalObjectsAsArrayPropertyCorrectly()
    {
        $someDataArray = [
            'bla' => 'blub',
            'another' => 'bla',
            'and another' => 'blub'
        ];

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(false));

        $objectSerializer = $this->getAccessibleMock(\Neos\Flow\ObjectManagement\ObjectSerializer::class, array('dummy'), array(), '', false);
        $objectSerializer->injectObjectManager($mockObjectManager);

        $objectSerializer->deserializeObjectsArray($someDataArray);
        $this->assertEquals($someDataArray, $objectSerializer->_get('objectsAsArray'), 'The data array has not been set as expected.');
    }

    /**
     * @test
     */
    public function deserializeObjectsArrayCallsReconstituteObjectWithTheCorrectObjectData()
    {
        $className = 'dummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {}');

        $className1 = 'class1' . md5(uniqid(mt_rand(), true));
        $object1 = $this->createMock($className, [], [], $className1, false);
        $className2 = 'class2' . md5(uniqid(mt_rand(), true));
        $object2 = $this->createMock($className, [], [], $className2, false);
        $className3 = 'class3' . md5(uniqid(mt_rand(), true));
        $object3 = $this->createMock($className, [], [], $className3, false);

        $objectsAsArray = [
            spl_object_hash($object1) => [
                ObjectSerializer::CLASSNAME => $className1,
                ObjectSerializer::PROPERTIES => [1],
            ],
            spl_object_hash($object2) => [
                ObjectSerializer::CLASSNAME => $className2,
                ObjectSerializer::PROPERTIES => [2],
            ],
            'someReferencedObject1' => [],
            spl_object_hash($object3) => [
                ObjectSerializer::CLASSNAME => $className3,
                ObjectSerializer::PROPERTIES => [3],
            ],
            'someReferencedObject2' => [],
            'someReferencedObject3' => [],
        ];

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(true));

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstituteObject', 'createEmptyObject'], [], '', false);
        $objectSerializer->expects($this->at(0))->method('reconstituteObject')->with(spl_object_hash($object1), $objectsAsArray[spl_object_hash($object1)])->will($this->returnValue($object1));
        $objectSerializer->expects($this->at(1))->method('reconstituteObject')->with(spl_object_hash($object2), $objectsAsArray[spl_object_hash($object2)])->will($this->returnValue($object2));
        $objectSerializer->expects($this->at(2))->method('reconstituteObject')->with(spl_object_hash($object3), $objectsAsArray[spl_object_hash($object3)])->will($this->returnValue($object3));

        $objectSerializer->injectObjectManager($mockObjectManager);

        $objects = $objectSerializer->deserializeObjectsArray($objectsAsArray);

        $this->assertEquals(
            [
                spl_object_hash($object1) => $object1,
                spl_object_hash($object2) => $object2,
                spl_object_hash($object3) => $object3
            ],
            $objects
        );
    }

    /**
     * @test
     */
    public function buildStorageArrayCreatesTheCorrectArrayForAnArrayProperty()
    {
        $objectSerializer = $this->getAccessibleMock(\Neos\Flow\ObjectManagement\ObjectSerializer::class, array('dummy'), array(), '', false);

        $expectedArray = [
            'key1' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 1,
            ],
            'key2' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 2,
            ],
            'key3' => [
                ObjectSerializer::TYPE => 'array',
                ObjectSerializer::VALUE => [
                    'key4' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 4,
                    ],
                    'key5' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 5,
                    ]
                ]
            ]
        ];

        $arrayProperty = [
            'key1' => 1,
            'key2' => 2,
            'key3' => [
                'key4' => 4,
                'key5' => 5
            ]
        ];

        $this->assertSame($expectedArray, $objectSerializer->_call('buildStorageArrayForArrayProperty', $arrayProperty));
    }

    /**
     * @test
     */
    public function buildStorageArrayCreatesTheCorrectArrayForAnArrayPropertyWithContainingObject()
    {
        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {}');
        $mockObject = $this->createMock($className);
        $objectName = spl_object_hash($mockObject);

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['serializeObjectAsPropertyArray'], [], '', false);
        $objectSerializer->expects($this->once())->method('serializeObjectAsPropertyArray')->with($mockObject);

        $arrayProperty = [
            'key1' => 1,
            'key2' => $mockObject,
        ];

        $expectedArray = [
            'key1' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 1,
            ],
            'key2' => [
                ObjectSerializer::TYPE => 'object',
                ObjectSerializer::VALUE => $objectName,
            ]
        ];

        $this->assertSame($expectedArray, $objectSerializer->_call('buildStorageArrayForArrayProperty', $arrayProperty));
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArrayForSplObjectStoragePropertyBuildsTheCorrectArrayStructureAndStoresEveryObjectInsideSeparately()
    {
        $propertyClassName1 = 'DummyClass' . md5(uniqid(mt_rand(), true));
        $propertyClassName2 = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $propertyClassName1 . ' {}');
        eval('class ' . $propertyClassName2 . ' {}');
        $propertyClass1 = new $propertyClassName1();
        $propertyClass2 = new $propertyClassName2();

        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $SplObjectProperty;

			public function __construct($object1, $object2) {
				$this->SplObjectProperty = new \SplObjectStorage();
				$this->SplObjectProperty->attach($object1);
				$this->SplObjectProperty->attach($object2);
			}

			public function getSplObjectProperty() {
				return $this->SplObjectProperty;
			}
		}');

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(['SplObjectProperty']));
        $mockReflectionService->expects($this->at(1))->method('getClassPropertyNames')->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(2))->method('getClassPropertyNames')->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(3))->method('getClassPropertyNames')->will($this->returnValue([]));
        $mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->with($className, 'SplObjectProperty', 'transient')->will($this->returnValue(false));

        $objectSerializer = new ObjectSerializer($this->getMockBuilder(SessionInterface::class)->disableOriginalConstructor()->getMock());
        $objectSerializer->injectReflectionService($mockReflectionService);

        $objectHash1 = spl_object_hash($propertyClass1);
        $objectHash2 = spl_object_hash($propertyClass2);

        $object = new $className($propertyClass1, $propertyClass2);
        $expectedArray = [
            spl_object_hash($object) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'SplObjectProperty' => [
                        ObjectSerializer::TYPE => 'SplObjectStorage',
                        ObjectSerializer::VALUE => [$objectHash1, $objectHash2]
                    ]
                ]
            ],
            $objectHash1 => [
                ObjectSerializer::CLASSNAME => $propertyClassName1,
                ObjectSerializer::PROPERTIES => [],
            ],
            $objectHash2 => [
                ObjectSerializer::CLASSNAME => $propertyClassName2,
                ObjectSerializer::PROPERTIES => [],
            ],
        ];

        $this->assertEquals($expectedArray, $objectSerializer->serializeObjectAsPropertyArray($object));
    }

    /**
     * @test
     */
    public function reconstituteObjectCallsTheCorrectReconstitutePropertyTypeFunctionsAndSetsTheValuesInTheObject()
    {
        $emptyClassName = 'emptyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $emptyClassName . ' {}');

        $className = 'someClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $simpleProperty;
			private $arrayProperty;
			private $arrayObjectProperty;
			private $objectProperty;
			private $splObjectStorageProperty;
			private $collectionProperty;
			private $persistenceObjectProperty;

			public function getSimpleProperty() { return $this->simpleProperty; }
			public function getArrayProperty() { return $this->arrayProperty; }
			public function getArrayObjectProperty() { return $this->arrayObjectProperty; }
			public function getObjectProperty() { return $this->objectProperty; }
			public function getSplObjectStorageProperty() { return $this->splObjectStorageProperty; }
			public function getCollectionProperty() { return $this->collectionProperty; }
			public function getPersistenceObjectProperty() { return $this->persistenceObjectProperty; }
		}');

        $objectData = [
            ObjectSerializer::CLASSNAME => $className,
            ObjectSerializer::PROPERTIES => [
                'simpleProperty' => [
                    ObjectSerializer::TYPE => 'simple',
                    ObjectSerializer::VALUE => 'simplePropertyValue',
                ],
                'arrayProperty' => [
                    ObjectSerializer::TYPE => 'array',
                    ObjectSerializer::VALUE => 'arrayPropertyValue',
                ],
                'arrayObjectProperty' => [
                    ObjectSerializer::TYPE => 'ArrayObject',
                    ObjectSerializer::VALUE => 'arrayObjectPropertyValue',
                ],
                'objectProperty' => [
                    ObjectSerializer::TYPE => 'object',
                    ObjectSerializer::VALUE => 'emptyClass'
                ],
                'splObjectStorageProperty' => [
                    ObjectSerializer::TYPE => 'SplObjectStorage',
                    ObjectSerializer::VALUE => ['splObjectStoragePropertyValue'],
                ],
                'collectionProperty' => [
                    ObjectSerializer::TYPE => 'Collection',
                    ObjectSerializer::CLASSNAME => 'Doctrine\Common\Collections\ArrayCollection',
                    ObjectSerializer::VALUE => ['collectionPropertyValue'],
                ],
                'persistenceObjectProperty' => [
                    ObjectSerializer::TYPE => 'persistenceObject',
                    ObjectSerializer::VALUE => 'persistenceObjectClassName:persistenceObjectUUID',
                ]
            ]
        ];

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->will($this->returnArgument(0));

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstituteArray', 'reconstituteSplObjectStorage', 'reconstituteCollection', 'reconstitutePersistenceObject'], [], '', false);
        $objectSerializer->injectObjectManager($mockObjectManager);
        $objectSerializer->expects($this->at(0))->method('reconstituteArray')->with('arrayPropertyValue')->will($this->returnValue('arrayPropertyValue'));
        $objectSerializer->expects($this->at(1))->method('reconstituteArray')->with('arrayObjectPropertyValue')->will($this->returnValue(['arrayObjectPropertyValue']));
        $objectSerializer->expects($this->once())->method('reconstituteSplObjectStorage')->with(['splObjectStoragePropertyValue'])->will($this->returnValue('splObjectStoragePropertyValue'));
        $objectSerializer->expects($this->once())->method('reconstituteCollection')->with('Doctrine\Common\Collections\ArrayCollection', ['collectionPropertyValue'])->will($this->returnValue('collectionPropertyValue'));
        $objectSerializer->expects($this->once())->method('reconstitutePersistenceObject')->with('persistenceObjectClassName', 'persistenceObjectUUID')->will($this->returnValue('persistenceObjectPropertyValue'));

        $objectsAsArray = [
            'emptyClass' => [
                ObjectSerializer::CLASSNAME => $emptyClassName,
                ObjectSerializer::PROPERTIES => [],
            ]
        ];
        $objectSerializer->_set('objectsAsArray', $objectsAsArray);

        $object = $objectSerializer->_call('reconstituteObject', 'dummyobjecthash', $objectData);

        $this->assertEquals('simplePropertyValue', $object->getSimpleProperty(), 'Simple property was not set as expected.');
        $this->assertEquals('arrayPropertyValue', $object->getArrayProperty(), 'Array property was not set as expected.');
        $this->assertEquals(new \ArrayObject(['arrayObjectPropertyValue']), $object->getArrayObjectProperty(), 'ArrayObject property was not set as expected.');
        $this->assertEquals(new $emptyClassName(), $object->getObjectProperty(), 'Object property was not set as expected.');
        $this->assertEquals('splObjectStoragePropertyValue', $object->getSplObjectStorageProperty(), 'SplObjectStorage property was not set as expected.');
        $this->assertEquals('collectionPropertyValue', $object->getCollectionProperty(), 'Collection property was not set as expected.');
        $this->assertEquals('persistenceObjectPropertyValue', $object->getPersistenceObjectProperty(), 'Persistence object property was not set as expected.');
    }

    /**
     * @test
     */
    public function reconstituteArrayWorks()
    {
        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['dummy'], [], '', false);

        $dataArray = [
            'key1' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 1,
            ],
            'key2' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 2,
            ],
            'key3' => [
                ObjectSerializer::TYPE => 'array',
                ObjectSerializer::VALUE => [
                    'key4' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 4,
                    ],
                    'key5' => [
                        ObjectSerializer::TYPE => 'simple',
                        ObjectSerializer::VALUE => 5,
                    ]
                ]
            ]
        ];

        $expectedArrayProperty = [
            'key1' => 1,
            'key2' => 2,
            'key3' => [
                'key4' => 4,
                'key5' => 5
            ]
        ];

        $this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
    }

    /**
     * @test
     */
    public function reconstituteArrayWorksWithObjectsInTheArray()
    {
        $objectsAsArray = [
            'some object' => [
                ObjectSerializer::CLASSNAME => 'some object',
                ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,
            ]
        ];

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstituteObject'], [], '', false);
        $objectSerializer->expects($this->once())->method('reconstituteObject')->with('some object', [ObjectSerializer::CLASSNAME => 'some object', ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,])->will($this->returnValue('reconstituted object'));
        $objectSerializer->_set('objectsAsArray', $objectsAsArray);


        $dataArray = [
            'key1' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 1,
            ],
            'key2' => [
                ObjectSerializer::TYPE => 'object',
                ObjectSerializer::VALUE => 'some object'
            ]
        ];

        $expectedArrayProperty = [
            'key1' => 1,
            'key2' => 'reconstituted object',
        ];

        $this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
    }

    /**
     * @test
     */
    public function reconstituteArrayWorksWithSplObjectStorageInTheArray()
    {
        $objectsAsArray = [
            'some object' => [
                ObjectSerializer::CLASSNAME => 'some object',
                ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,
            ]
        ];

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstituteSplObjectStorage'], [], '', false);
        $objectSerializer->expects($this->once())->method('reconstituteSplObjectStorage')->with([ObjectSerializer::CLASSNAME => 'some object', ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,])->will($this->returnValue('reconstituted object'));
        $objectSerializer->_set('objectsAsArray', $objectsAsArray);


        $dataArray = [
            'key1' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 1,
            ],
            'key2' => [
                ObjectSerializer::TYPE => 'SplObjectStorage',
                ObjectSerializer::VALUE => 'some object'
            ]
        ];

        $expectedArrayProperty = [
            'key1' => 1,
            'key2' => 'reconstituted object',
        ];

        $this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
    }

    /**
     * @test
     */
    public function reconstituteArrayWorksWithPersistenceObjectsInTheArray()
    {
        $objectsAsArray = [
            'some object' => [
                ObjectSerializer::CLASSNAME => 'some object',
                ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,
            ]
        ];

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstitutePersistenceObject'], [], '', false);
        $objectSerializer->expects($this->once())->method('reconstitutePersistenceObject')->with('persistenceObjectClassName', 'someUUID')->will($this->returnValue('reconstituted object'));
        $objectSerializer->_set('objectsAsArray', $objectsAsArray);

        $dataArray = [
            'key1' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 1,
            ],
            'key2' => [
                ObjectSerializer::TYPE => 'persistenceObject',
                ObjectSerializer::VALUE => [
                    ObjectSerializer::CLASSNAME => 'persistenceObjectClassName',
                    'UUID' => 'someUUID',
                ]
            ]
        ];

        $expectedArrayProperty = [
            'key1' => 1,
            'key2' => 'reconstituted object',
        ];

        $this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
    }

    /**
     * @test
     */
    public function reconstituteSplObjectStorageWorks()
    {
        $mockObject1 = new \stdClass();
        $mockObject2 = new \stdClass();

        $objectsAsArray = [
            'some object' => ['object1 data'],
            'some other object' => ['object2 data']
        ];

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstituteObject'], [], '', false);
        $objectSerializer->expects($this->at(0))->method('reconstituteObject')->with('some object', ['object1 data'])->will($this->returnValue($mockObject1));
        $objectSerializer->expects($this->at(1))->method('reconstituteObject')->with('some other object', ['object2 data'])->will($this->returnValue($mockObject2));
        $objectSerializer->_set('objectsAsArray', $objectsAsArray);


        $dataArray = ['some object', 'some other object'];

        $expectedResult = new \SplObjectStorage();
        $expectedResult->attach($mockObject1);
        $expectedResult->attach($mockObject2);

        $this->assertEquals($expectedResult, $objectSerializer->_call('reconstituteSplObjectStorage', $dataArray), 'The SplObjectStorage was not reconstituted correctly.');
    }

    /**
     * @test
     */
    public function reconstitutePersistenceObjectRetrievesTheObjectCorrectlyFromThePersistenceFramework()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('someUUID')->will($this->returnValue('theObject'));

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['dummy'], [], '', false);
        $objectSerializer->injectPersistenceManager($mockPersistenceManager);

        $this->assertEquals('theObject', $objectSerializer->_call('reconstitutePersistenceObject', 'someClassName', 'someUUID'));
    }

    /**
     * @test
     */
    public function serializeObjectAsPropertyArrayForDoctrineCollectionPropertyBuildsTheCorrectArrayStructureAndStoresEveryObjectInsideSeparately()
    {
        $propertyClassName1 = 'DummyClass' . md5(uniqid(mt_rand(), true));
        $propertyClassName2 = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $propertyClassName1 . ' {}');
        eval('class ' . $propertyClassName2 . ' {}');
        $propertyClass1 = new $propertyClassName1();
        $propertyClass2 = new $propertyClassName2();

        $className = 'DummyClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
			private $collectionProperty;

			public function __construct($object1, $object2) {
				$this->collectionProperty = new \Doctrine\Common\Collections\ArrayCollection();
				$this->collectionProperty->add($object1);
				$this->collectionProperty->add($object2);
			}

			public function getCollectionProperty() {
				return $this->collectionProperty;
			}
		}');

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(['collectionProperty']));
        $mockReflectionService->expects($this->at(1))->method('getClassPropertyNames')->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(2))->method('getClassPropertyNames')->will($this->returnValue([]));
        $mockReflectionService->expects($this->at(3))->method('getClassPropertyNames')->will($this->returnValue([]));
        $mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->with($className, 'collectionProperty', 'transient')->will($this->returnValue(false));

        $objectSerializer = new ObjectSerializer($this->getMockBuilder(SessionInterface::class)->disableOriginalConstructor()->getMock());
        $objectSerializer->injectReflectionService($mockReflectionService);

        $objectHash1 = spl_object_hash($propertyClass1);
        $objectHash2 = spl_object_hash($propertyClass2);

        $object = new $className($propertyClass1, $propertyClass2);
        $expectedArray = [
            $objectHash1 => [
                ObjectSerializer::CLASSNAME => $propertyClassName1,
                ObjectSerializer::PROPERTIES => [],
            ],
            $objectHash2 => [
                ObjectSerializer::CLASSNAME => $propertyClassName2,
                ObjectSerializer::PROPERTIES => [],
            ],
            spl_object_hash($object) => [
                ObjectSerializer::CLASSNAME => $className,
                ObjectSerializer::PROPERTIES => [
                    'collectionProperty' => [
                        ObjectSerializer::TYPE => 'Collection',
                        ObjectSerializer::CLASSNAME => 'Doctrine\Common\Collections\ArrayCollection',
                        ObjectSerializer::VALUE => [$objectHash1, $objectHash2]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedArray, $objectSerializer->serializeObjectAsPropertyArray($object));
    }

    /**
     * @test
     */
    public function reconstituteCollectionWorks()
    {
        $mockObject1 = new \stdClass();
        $mockObject2 = new \stdClass();

        $objectsAsArray = [
            'some object' => ['object1 data'],
            'some other object' => ['object2 data']
        ];

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstituteObject'], [], '', false);
        $objectSerializer->expects($this->at(0))->method('reconstituteObject')->with('some object', ['object1 data'])->will($this->returnValue($mockObject1));
        $objectSerializer->expects($this->at(1))->method('reconstituteObject')->with('some other object', ['object2 data'])->will($this->returnValue($mockObject2));
        $objectSerializer->_set('objectsAsArray', $objectsAsArray);


        $dataArray = ['some object', 'some other object'];

        $expectedResult = new \Doctrine\Common\Collections\ArrayCollection();
        $expectedResult->add($mockObject1);
        $expectedResult->add($mockObject2);

        $this->assertEquals($expectedResult, $objectSerializer->_call('reconstituteCollection', 'Doctrine\Common\Collections\ArrayCollection', $dataArray), 'The Collection was not reconstituted correctly.');
    }

    /**
     * @test
     */
    public function reconstituteArrayWorksWithCollectionInTheArray()
    {
        $objectsAsArray = [
            'some object' => [
                ObjectSerializer::CLASSNAME => 'some object',
                ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,
            ]
        ];

        $objectSerializer = $this->getAccessibleMock(ObjectSerializer::class, ['reconstituteCollection'], [], '', false);
        $objectSerializer->expects($this->once())->method('reconstituteCollection')->with('Doctrine\Common\Collections\ArrayCollection', ['some object'])->will($this->returnValue('reconstituted object'));
        $objectSerializer->_set('objectsAsArray', $objectsAsArray);


        $dataArray = [
            'key1' => [
                ObjectSerializer::TYPE => 'simple',
                ObjectSerializer::VALUE => 1,
            ],
            'key2' => [
                ObjectSerializer::TYPE => 'Collection',
                ObjectSerializer::CLASSNAME => 'Doctrine\Common\Collections\ArrayCollection',
                ObjectSerializer::VALUE => ['some object']
            ]
        ];

        $expectedArrayProperty = [
            'key1' => 1,
            'key2' => 'reconstituted object'
        ];

        $this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
    }
}
