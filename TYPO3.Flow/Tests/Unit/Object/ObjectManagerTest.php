<?php
namespace TYPO3\Flow\Tests\Unit\Object;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/Fixture/BasicClass.php');

use TYPO3\Flow\Object\ObjectManager;
use TYPO3\Flow\Tests\Object\Fixture\BasicClass;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Object\Configuration\Configuration as ObjectConfiguration;

class ObjectManagerTest extends UnitTestCase
{
    public function factoryGenerationDataProvider()
    {
        return [
            'generatePrototype' => [
                'scope' => ObjectConfiguration::SCOPE_PROTOTYPE,
                'factoryCalls' => 2
            ],
            'generateSingleton' => [
                'scope' => ObjectConfiguration::SCOPE_SINGLETON,
                'factoryCalls' => 1
            ]
        ];
    }

    /**
     * @test
     * @dataProvider factoryGenerationDataProvider
     *
     * @param integer $scope
     * @param integer $factoryCalls
     */
    public function getFactoryGeneratedPrototypeObject($scope, $factoryCalls)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildObjectByFactory'])->getMock();
        $objectManager->expects($this->exactly($factoryCalls))
            ->method('buildObjectByFactory')->will($this->returnCallback(function () {
                return new BasicClass();
            }));

        $objects = [
            BasicClass::class => [
                'f' => 'SomeFactory',
                's' => $scope
            ]
        ];
        $objectManager->setObjects($objects);

        $object1 = $objectManager->get(BasicClass::class);
        $object2 = $objectManager->get(BasicClass::class);

        if ($scope == ObjectConfiguration::SCOPE_PROTOTYPE) {
            $this->assertNotSame($object1, $object2);
        } else {
            $this->assertSame($object1, $object2);
        }
    }
}
