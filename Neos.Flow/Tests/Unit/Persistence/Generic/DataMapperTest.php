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
use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Persistence;

/**
 * Testcase for \Neos\Flow\Persistence\DataMapper
 */
class DataMapperTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\Flow\Persistence\Generic\Exception\InvalidObjectDataException
     */
    public function mapToObjectThrowsExceptionOnEmptyInput()
    {
        $objectData = [];

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $dataMapper->_call('mapToObject', $objectData);
    }

    /**
     * @test
     */
    public function mapToObjectsMapsArrayToObjectByCallingMapToObject()
    {
        $objectData = [['identifier' => '1234']];
        $object = new \stdClass();

        $dataMapper = $this->getMockBuilder(Persistence\Generic\DataMapper::class)->setMethods(['mapToObject'])->getMock();
        $dataMapper->expects($this->once())->method('mapToObject')->with($objectData[0])->will($this->returnValue($object));

        $dataMapper->mapToObjects($objectData);
    }

    /**
     * @test
     */
    public function mapToObjectReturnsObjectFromIdentityMapIfAvailable()
    {
        $objectData = ['identifier' => '1234'];
        $object = new \stdClass();

        $mockSession = $this->createMock(Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with('1234')->will($this->returnValue(true));
        $mockSession->expects($this->once())->method('getObjectByIdentifier')->with('1234')->will($this->returnValue($object));

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $dataMapper->injectPersistenceSession($mockSession);
        $dataMapper->_call('mapToObject', $objectData);
    }

    /**
     * Test that an object is reconstituted, registered with the identity map
     * and memorizes it's clean state.
     *
     * @test
     */
    public function mapToObjectReconstitutesExpectedObjectAndRegistersItWithIdentityMapToObjects()
    {
        $mockEntityClassName = 'Entity' . md5(uniqid(mt_rand(), true));
        $mockEntity = $this->createMock(ProxyInterface::class, ['Flow_Aop_Proxy_invokeJoinPoint', '__wakeup'], [], $mockEntityClassName);

        $objectData = ['identifier' => '1234', 'classname' => $mockEntityClassName, 'properties' => ['foo']];

        $mockClassSchema = $this->getMockBuilder(ClassSchema::class)->disableOriginalConstructor()->getMock();
        $mockClassSchema->expects($this->any())->method('getModelType')->will($this->returnValue(ClassSchema::MODELTYPE_ENTITY));
        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getClassSchema')->with($mockEntityClassName)->will($this->returnValue($mockClassSchema));
        $mockSession = $this->createMock(Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('registerReconstitutedEntity')->with($mockEntity, $objectData);
        $mockSession->expects($this->once())->method('registerObject')->with($mockEntity, '1234');

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['thawProperties']);
        $dataMapper->expects($this->once())->method('thawProperties')->with($mockEntity, $objectData['identifier'], $objectData);
        $dataMapper->injectPersistenceSession($mockSession);
        $dataMapper->injectReflectionService($mockReflectionService);
        $dataMapper->_call('mapToObject', $objectData);
    }

    /**
     * @test
     */
    public function thawPropertiesSetsPropertyValues()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $firstProperty; public $secondProperty; public $thirdProperty; public $fourthProperty; }');
        $object = new $className();

        $objectData = [
            'identifier' => '1234',
            'classname' => 'TYPO3\Post',
            'properties' => [
                'firstProperty' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'firstValue'
                ],
                'secondProperty' => [
                    'type' => 'integer',
                    'multivalue' => false,
                    'value' => 1234
                ],
                'thirdProperty' => [
                    'type' => 'float',
                    'multivalue' => false,
                    'value' => 1.234
                ],
                'fourthProperty' => [
                    'type' => 'boolean',
                    'multivalue' => false,
                    'value' => false
                ]
            ]
        ];

        $classSchema = new ClassSchema('TYPO3\Post');
        $classSchema->addProperty('firstProperty', 'string');
        $classSchema->addProperty('secondProperty', 'integer');
        $classSchema->addProperty('thirdProperty', 'float');
        $classSchema->addProperty('fourthProperty', 'boolean');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $dataMapper->injectReflectionService($mockReflectionService);
        $dataMapper->_call('thawProperties', $object, 1234, $objectData);
        $this->assertAttributeEquals('firstValue', 'firstProperty', $object);
        $this->assertAttributeEquals(1234, 'secondProperty', $object);
        $this->assertAttributeEquals(1.234, 'thirdProperty', $object);
        $this->assertAttributeEquals(false, 'fourthProperty', $object);
    }

    /**
     * After thawing the properties, the nodes' uuid will be available in the identifier
     * property of the proxy class.
     *
     * @test
     */
    public function thawPropertiesAssignsTheUuidToTheProxy()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $Flow_Persistence_Entity_UUID; }');
        $object = new $className();

        $objectData = [
            'identifier' => 'c254d2e0-825a-11de-8a39-0800200c9a66',
            'classname' => 'TYPO3\Post',
            'properties' => []
        ];

        $classSchema = new ClassSchema('TYPO3\Post');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $dataMapper->injectReflectionService($mockReflectionService);
        $dataMapper->_call('thawProperties', $object, $objectData['identifier'], $objectData);

        $this->assertAttributeEquals('c254d2e0-825a-11de-8a39-0800200c9a66', 'Persistence_Object_Identifier', $object);
    }

    /**
     * @test
     */
    public function thawPropertiesDelegatesHandlingOfArraysAndObjects()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $firstProperty; public $secondProperty; public $thirdProperty; public $fourthProperty; }');
        $object = new $className();

        $objectData = [
            'identifier' => '1234',
            'classname' => 'Neos\Post',
            'properties' => [
                'firstProperty' => [
                    'type' => 'array',
                    'multivalue' => true,
                    'value' => [['type' => 'string', 'index' => 0, 'value' => 'theMappedArray']]
                ],
                'secondProperty' => [
                    'type' => 'SplObjectStorage',
                    'multivalue' => true,
                    'value' => [['type' => 'Some\Object', 'index' => null, 'value' => 'theMappedSplObjectStorage']]
                ],
                'thirdProperty' => [
                    'type' => 'DateTime',
                    'multivalue' => false,
                    'value' => 'theUnixtime'
                ],
                'fourthProperty' => [
                    'type' => 'Neos\Some\Domain\Model',
                    'multivalue' => false,
                    'value' => ['identifier' => 'theMappedObjectIdentifier']
                ]
            ]
        ];

        $classSchema = new ClassSchema('Neos\Post');
        $classSchema->addProperty('firstProperty', 'array');
        $classSchema->addProperty('secondProperty', 'SplObjectStorage');
        $classSchema->addProperty('thirdProperty', 'DateTime');
        $classSchema->addProperty('fourthProperty', 'Neos\Some\Domain\Model');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['mapDateTime', 'mapArray', 'mapSplObjectStorage', 'mapToObject']);
        $dataMapper->injectReflectionService($mockReflectionService);
        $dataMapper->expects($this->at(0))->method('mapArray')->with($objectData['properties']['firstProperty']['value']);
        $dataMapper->expects($this->at(1))->method('mapSplObjectStorage')->with($objectData['properties']['secondProperty']['value']);
        $dataMapper->expects($this->at(2))->method('mapDateTime')->with($objectData['properties']['thirdProperty']['value']);
        $dataMapper->expects($this->at(3))->method('mapToObject')->with($objectData['properties']['fourthProperty']['value']);
        $dataMapper->_call('thawProperties', $object, $objectData['identifier'], $objectData);
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/9684
     */
    public function thawPropertiesFollowsOrderOfGivenObjectData()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { protected $properties = []; public function __set($name, $value) { $this->properties[] = [$name => $value]; } }');
        $object = new $className();

        $objectData = [
            'identifier' => '1234',
            'classname' => 'TYPO3\Post',
            'properties' => [
                'secondProperty' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'secondValue'
                ],
                'firstProperty' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'firstValue'
                ],
                'thirdProperty' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'thirdValue'
                ]
            ]
        ];

        $classSchema = new ClassSchema('TYPO3\Post');
        $classSchema->addProperty('firstProperty', 'string');
        $classSchema->addProperty('secondProperty', 'string');
        $classSchema->addProperty('thirdProperty', 'string');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $dataMapper->injectReflectionService($mockReflectionService);
        $dataMapper->_call('thawProperties', $object, '1234', $objectData);

        // the order of setting those is important, but cannot be tested for now (static setProperty)
        $expected = [['secondProperty' => 'secondValue'],['firstProperty' => 'firstValue'],['thirdProperty' => 'thirdValue'],['Persistence_Object_Identifier' => '1234']];
        $this->assertAttributeSame($expected, 'properties', $object);
    }

    /**
     * If a property has been removed from a class old data still in the persistence
     * must be skipped when reconstituting.
     *
     * @test
     */
    public function thawPropertiesSkipsPropertiesNoLongerInClassSchema()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $firstProperty; public $thirdProperty; }');
        $object = new $className();

        $objectData = [
            'identifier' => '1234',
            'classname' => 'TYPO3\Post',
            'properties' => [
                'firstProperty' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'firstValue'
                ],
                'secondProperty' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'secondValue'
                ],
                'thirdProperty' => [
                    'type' => 'string',
                    'multivalue' => false,
                    'value' => 'thirdValue'
                ]
            ]
        ];

        $classSchema = new ClassSchema('TYPO3\Post');
        $classSchema->addProperty('firstProperty', 'string');
        $classSchema->addProperty('thirdProperty', 'string');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $dataMapper->injectReflectionService($mockReflectionService);
        $dataMapper->_call('thawProperties', $object, 1234, $objectData);

        $this->assertObjectNotHasAttribute('secondProperty', $object);
    }

    /**
     * After thawing the properties, metadata in the object data will be set
     * as a special proxy property.
     *
     * @test
     */
    public function thawPropertiesAssignsMetadataToTheProxyIfItExists()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' { public $Flow_Persistence_Metadata; }');
        $object = new $className();

        $objectData = [
            'identifier' => 'c254d2e0-825a-11de-8a39-0800200c9a66',
            'classname' => 'TYPO3\Post',
            'properties' => [],
            'metadata' => ['My_Metadata' => 'Test']
        ];

        $classSchema = new ClassSchema('TYPO3\Post');

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $dataMapper->injectReflectionService($mockReflectionService);
        $dataMapper->_call('thawProperties', $object, $objectData['identifier'], $objectData);

        $this->assertAttributeEquals(['My_Metadata' => 'Test'], 'Flow_Persistence_Metadata', $object);
    }

    /**
     * @test
     */
    public function mapSplObjectStorageCreatesSplObjectStorage()
    {
        $objectData = [
            ['value' => ['mappedObject1']],
            ['value' => ['mappedObject2']]
        ];

        $classSchema = new ClassSchema('TYPO3\Post');
        $classSchema->addProperty('firstProperty', 'SplObjectStorage');

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['mapToObject']);
        $dataMapper->expects($this->at(0))->method('mapToObject')->with($objectData[0]['value'])->will($this->returnValue(new \stdClass()));
        $dataMapper->expects($this->at(1))->method('mapToObject')->with($objectData[1]['value'])->will($this->returnValue(new \stdClass()));
        $dataMapper->_call('mapSplObjectStorage', $objectData);
    }

    /**
     * @test
     */
    public function mapDateTimeCreatesDateTimeFromTimestamp()
    {
        $expected = new \DateTime();
        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $this->assertEquals($dataMapper->_call('mapDateTime', $expected->getTimestamp())->format(\DateTime::W3C), $expected->format(\DateTime::W3C));
    }

    /**
     * @test
     */
    public function mapArrayCreatesExpectedArray()
    {
        $dateTime = new \DateTime();
        $object = new \stdClass();
        $splObjectStorage = new \SplObjectStorage();

        $expected = [
            'one' => 'onetwothreefour',
            'two' => 1234,
            'three' => 1.234,
            'four' => false,
            'five' => $dateTime,
            'six' => $object,
            'seven' => $splObjectStorage
        ];

        $arrayValues = [
            'one' => [
                'type' => 'string',
                'index' => 'one',
                'value' => 'onetwothreefour'
            ],
            'two' => [
                'type' => 'integer',
                'index' => 'two',
                'value' => 1234
            ],
            'three' => [
                'type' => 'float',
                'index' => 'three',
                'value' =>  1.234
            ],
            'four' => [
                'type' => 'boolean',
                'index' => 'four',
                'value' => false
            ],
            'five' => [
                'type' => 'DateTime',
                'index' => 'five',
                'value' => $dateTime->getTimestamp()
            ],
            'six' => [
                'type' => 'stdClass',
                'index' => 'six',
                'value' => ['mappedObject']
            ],
            'seven' => [
                'type' => 'SplObjectStorage',
                'index' => 'seven',
                'value' => ['mappedObject']
            ]
        ];

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['mapDateTime', 'mapToObject', 'mapSplObjectStorage']);
        $dataMapper->expects($this->once())->method('mapDateTime')->with($arrayValues['five']['value'])->will($this->returnValue($dateTime));
        $dataMapper->expects($this->once())->method('mapToObject')->with($arrayValues['six']['value'])->will($this->returnValue($object));
        $dataMapper->expects($this->once())->method('mapSplObjectStorage')->with($arrayValues['seven']['value'])->will($this->returnValue($splObjectStorage));
        $this->assertEquals($dataMapper->_call('mapArray', $arrayValues), $expected);
    }


    /**
     * @test
     */
    public function mapArrayMapsNestedArray()
    {
        $arrayValues = [
            'one' => [
                'type' => 'array',
                'index' => 'foo',
                'value' => [
                    [
                        'type' => 'string',
                        'index' => 'bar',
                        'value' => 'baz'
                    ],
                    [
                        'type' => 'integer',
                        'index' => 'quux',
                        'value' => null
                    ]
                ]
            ]
        ];

        $expected = ['foo' => ['bar' => 'baz', 'quux' => null]];

        $dataMapper = $this->getAccessibleMock(Persistence\Generic\DataMapper::class, ['dummy']);
        $this->assertEquals($expected, $dataMapper->_call('mapArray', $arrayValues));
    }
}
