<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement;

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

use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\BasicClass;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;

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
        $objectManager->expects(self::exactly($factoryCalls))
            ->method('buildObjectByFactory')->will(self::returnCallBack(function () {
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
            self::assertNotSame($object1, $object2);
        } else {
            self::assertSame($object1, $object2);
        }
    }

    /**
     * @test
     */
    public function staticFactoryGeneratedPrototypeObject()
    {
        $objects = [
            BasicClass::class => [
                'f' => ['', 'Neos\Flow\Tests\Unit\ObjectManagement\Fixture\StaticFactory::create'],
                'fa' => [
                    ['t' => ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE, 'v' => 'Foo']
                ],
                's' => ObjectConfiguration::SCOPE_PROTOTYPE
            ]
        ];

        $context = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $objectManager = new ObjectManager($context);
        $objectManager->setObjects($objects);

        $instance = $objectManager->get(BasicClass::class);
        self::assertInstanceOf(BasicClass::class, $instance);
        self::assertSame($instance->getSomeProperty(), 'Foo');
    }
}
