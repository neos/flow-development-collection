<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\ObjectManagement;

use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\BasicClass;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/Fixture/BasicClass.php');
require_once(__DIR__ . '/Fixture/StaticFactory.php');

final class ObjectManagerTest extends UnitTestCase
{
    public static function factoryGenerationDataProvider(): \Iterator
    {
        yield 'generatePrototype' => [
            'scope' => ObjectConfiguration::SCOPE_PROTOTYPE,
            'factoryCalls' => 2
        ];
        yield 'generateSingleton' => [
            'scope' => ObjectConfiguration::SCOPE_SINGLETON,
            'factoryCalls' => 1
        ];
    }

    /**
     * @param int $scope
     * @param int $factoryCalls
     */
    #[DataProvider('factoryGenerationDataProvider')]
    #[Test]
    public function getFactoryGeneratedPrototypeObject($scope, $factoryCalls)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildObjectByFactory'])->getMock();
        $objectManager->expects($this->exactly($factoryCalls))
            ->method('buildObjectByFactory')->willReturnCallback(function () {
                return new BasicClass();
            });

        $objects = [
            BasicClass::class => [
                'f' => 'SomeFactory',
                's' => $scope
            ]
        ];
        $objectManager->setObjects($objects);

        $object1 = $objectManager->get(BasicClass::class);
        $object2 = $objectManager->get(BasicClass::class);

        if ($scope === ObjectConfiguration::SCOPE_PROTOTYPE) {
            self::assertNotSame($object1, $object2);
        } else {
            self::assertSame($object1, $object2);
        }
    }

    #[Test]
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

        $context = $this->createStub(ApplicationContext::class);
        $objectManager = new ObjectManager($context);
        $objectManager->setObjects($objects);

        $instance = $objectManager->get(BasicClass::class);
        self::assertInstanceOf(BasicClass::class, $instance);
        self::assertSame('Foo', $instance->getSomeProperty());
    }
}
