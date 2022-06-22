<?php
namespace Neos\Flow\Tests\Unit\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Neos\Flow\Persistence\Doctrine\EntityManagerConfiguration;
use Neos\Flow\Tests\UnitTestCase;

class EntityManagerConfigurationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function dqlCustomStringFunctionCanCorrectlyBeAppliedToConfiguration()
    {
        $configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

        self::assertEquals('Some\Foo\StringClass', $configuration->getCustomStringFunction('FOOSTRING'));
        self::assertEquals('Some\Bar\StringClass', $configuration->getCustomStringFunction('BARSTRING'));
    }

    /**
     * @test
     */
    public function dqlCustomNumericFunctionCanCorrectlyBeAppliedToConfiguration()
    {
        $configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

        self::assertEquals('Some\Foo\NumericClass', $configuration->getCustomNumericFunction('FOONUMERIC'));
        self::assertEquals('Some\Bar\NumericClass', $configuration->getCustomNumericFunction('BARNUMERIC'));
    }

    /**
     * @test
     */
    public function dqlCustomDateTimeFunctionCanCorrectlyBeAppliedToConfiguration()
    {
        $configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

        self::assertEquals('Some\Foo\DateTimeClass', $configuration->getCustomDatetimeFunction('FOODATETIME'));
        self::assertEquals('Some\Bar\DateTimeClass', $configuration->getCustomDatetimeFunction('BARDATETIME'));
    }

    /**
     * @return \Doctrine\ORM\Configuration
     */
    protected function buildAndPrepareDqlCustomStringConfiguration()
    {
        /** @var EntityManagerConfiguration $entityManagerConfiguration */
        $entityManagerConfiguration = $this->getAccessibleMock(EntityManagerConfiguration::class, ['applyCacheConfiguration']);
        /** @var Connection $connection */
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        /** @var EventManager $eventManager */
        $eventManager = $this->getMockBuilder(EventManager::class)->disableOriginalConstructor()->getMock();
        $configuration = new \Doctrine\ORM\Configuration;

        $settingsArray = [
            'persistence' => [
                'doctrine' => [
                    'dql' => [
                        'customStringFunctions' => [
                            'FOOSTRING' => 'Some\Foo\StringClass',
                            'BARSTRING' => 'Some\Bar\StringClass'
                        ],
                        'customNumericFunctions' => [
                            'FOONUMERIC' => 'Some\Foo\NumericClass',
                            'BARNUMERIC' => 'Some\Bar\NumericClass'
                        ],
                        'customDatetimeFunctions' => [
                            'FOODATETIME' => 'Some\Foo\DateTimeClass',
                            'BARDATETIME' => 'Some\Bar\DateTimeClass'
                        ],
                    ]
                ]
            ]
        ];

        $entityManagerConfiguration->injectSettings($settingsArray);
        $entityManagerConfiguration->configureEntityManager($connection, $configuration, $eventManager);
        return $configuration;
    }
}
