<?php
namespace Neos\Flow\Tests\Unit\Persistence\Aspect;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Persistence\Aspect\PersistenceMagicAspect;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PersistenceMagicAspect
 */
class PersistenceMagicAspectTest extends UnitTestCase
{
    /**
     * @var PersistenceMagicAspect
     */
    protected $persistenceMagicAspect;

    /**
     * @var JoinPointInterface
     */
    protected $mockJoinPoint;

    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->persistenceMagicAspect = $this->getAccessibleMock(PersistenceMagicAspect::class, ['dummy'], []);

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->persistenceMagicAspect->_set('persistenceManager', $this->mockPersistenceManager);

        $this->mockJoinPoint = $this->createMock(JoinPointInterface::class);
    }

    /**
     * @test
     * @return void
     */
    public function cloneObjectMarksTheObjectAsCloned()
    {
        $object = new \stdClass();
        $this->mockJoinPoint->expects(self::any())->method('getProxy')->will(self::returnValue($object));

        $this->persistenceMagicAspect->cloneObject($this->mockJoinPoint);
        self::assertTrue($object->Flow_Persistence_clone);
    }

    /**
     * @test
     * @return void
     */
    public function generateUuidGeneratesUuidAndRegistersProxyAsNewObject()
    {
        $className = 'Class' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' implements \Neos\Flow\Persistence\Aspect\PersistenceMagicInterface { public $Persistence_Object_Identifier = NULL; }');
        $object = new $className();

        $this->mockJoinPoint->expects(self::atLeastOnce())->method('getProxy')->will(self::returnValue($object));
        $this->mockPersistenceManager->expects(self::atLeastOnce())->method('registerNewObject')->with($object);
        $this->persistenceMagicAspect->generateUuid($this->mockJoinPoint);

        self::assertEquals(36, strlen($object->Persistence_Object_Identifier));
    }
}
