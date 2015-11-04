<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Doctrine;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 */
class EntityManagerFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        $entityManagerFactory = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Doctrine\EntityManagerFactory::class, array('dummy'));
        $configuration = new \Doctrine\ORM\Configuration;

        $settingsArray = array(
            'customStringFunctions' => array(
                'FOOSTRING' => 'Some\Foo\StringClass',
                'BARSTRING' => 'Some\Bar\StringClass'
            ),
            'customNumericFunctions' => array(
                'FOONUMERIC' => 'Some\Foo\NumericClass',
                'BARNUMERIC' => 'Some\Bar\NumericClass'
            ),
            'customDatetimeFunctions' => array(
                'FOODATETIME' => 'Some\Foo\DateTimeClass',
                'BARDATETIME' => 'Some\Bar\DateTimeClass'
            ),
        );
        $entityManagerFactory->_call('applyDqlSettingsToConfiguration', $settingsArray, $configuration);
        return $configuration;
    }
}
