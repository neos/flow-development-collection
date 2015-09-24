<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Aspect;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect;

/**
 * Testcase for the PersistenceMagicAspect
 *
 */
class PersistenceMagicAspectTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var PersistenceMagicAspect
     */
    protected $persistenceMagicAspect;

    /**
     * @var \TYPO3\Flow\Aop\JoinPointInterface
     */
    protected $mockJoinPoint;

    /**
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * Sets up this test case
     */
    public function setUp()
    {
        $this->persistenceMagicAspect = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect::class, array('dummy'), array());

        $this->mockPersistenceManager = $this->getMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $this->persistenceMagicAspect->_set('persistenceManager', $this->mockPersistenceManager);

        $this->mockJoinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class);
    }

    /**
     * @test
     * @return void
     */
    public function cloneObjectMarksTheObjectAsCloned()
    {
        $object = new \stdClass();
        $this->mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

        $this->persistenceMagicAspect->cloneObject($this->mockJoinPoint);
        $this->assertTrue($object->Flow_Persistence_clone);
    }

    /**
     * @test
     * @return void
     */
    public function generateUuidGeneratesUuidAndRegistersProxyAsNewObject()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' implements \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface { public $Persistence_Object_Identifier = NULL; }');
        $object = new $className();

        $this->mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('registerNewObject')->with($object);
        $this->persistenceMagicAspect->generateUuid($this->mockJoinPoint);

        $this->assertEquals(36, strlen($object->Persistence_Object_Identifier));
    }
}
