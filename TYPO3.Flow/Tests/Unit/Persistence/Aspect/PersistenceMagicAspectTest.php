<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $this->persistenceMagicAspect = $this->getAccessibleMock('TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect', array('dummy'), array());

        $this->mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $this->persistenceMagicAspect->_set('persistenceManager', $this->mockPersistenceManager);

        $this->mockJoinPoint = $this->createMock('TYPO3\Flow\Aop\JoinPointInterface');
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
