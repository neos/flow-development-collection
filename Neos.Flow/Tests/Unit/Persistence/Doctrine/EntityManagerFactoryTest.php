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

use Neos\Flow\Persistence\Doctrine\EntityManagerFactory;
use Neos\Flow\Tests\UnitTestCase;

class EntityManagerFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function dqlCustomStringFunctionCanCorrectlyBeAppliedToConfiguration()
    {
        $configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

        $this->assertEquals('Some\Foo\StringClass', $configuration->getCustomStringFunction('FOOSTRING'));
        $this->assertEquals('Some\Bar\StringClass', $configuration->getCustomStringFunction('BARSTRING'));
    }

    /**
     * @test
     */
    public function dqlCustomNumericFunctionCanCorrectlyBeAppliedToConfiguration()
    {
        $configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

        $this->assertEquals('Some\Foo\NumericClass', $configuration->getCustomNumericFunction('FOONUMERIC'));
        $this->assertEquals('Some\Bar\NumericClass', $configuration->getCustomNumericFunction('BARNUMERIC'));
    }

    /**
     * @test
     */
    public function dqlCustomDateTimeFunctionCanCorrectlyBeAppliedToConfiguration()
    {
        $configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

        $this->assertEquals('Some\Foo\DateTimeClass', $configuration->getCustomDatetimeFunction('FOODATETIME'));
        $this->assertEquals('Some\Bar\DateTimeClass', $configuration->getCustomDatetimeFunction('BARDATETIME'));
    }

    /**
     * @return \Doctrine\ORM\Configuration
     */
    protected function buildAndPrepareDqlCustomStringConfiguration()
    {
        $entityManagerFactory = $this->getAccessibleMock(EntityManagerFactory::class, ['dummy']);
        $configuration = new \Doctrine\ORM\Configuration;

        $settingsArray = [
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
        ];
        $entityManagerFactory->_call('applyDqlSettingsToConfiguration', $settingsArray, $configuration);
        return $configuration;
    }
}
