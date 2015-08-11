<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 */
class EntityManagerFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function dqlCustomStringFunctionCanCorrectlyBeAppliedToConfiguration() {
		$configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

		$this->assertEquals('Some\Foo\StringClass', $configuration->getCustomStringFunction('FOOSTRING'));
		$this->assertEquals('Some\Bar\StringClass', $configuration->getCustomStringFunction('BARSTRING'));
	}

	/**
	 * @test
	 */
	public function dqlCustomNumericFunctionCanCorrectlyBeAppliedToConfiguration() {
		$configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

		$this->assertEquals('Some\Foo\NumericClass', $configuration->getCustomNumericFunction('FOONUMERIC'));
		$this->assertEquals('Some\Bar\NumericClass', $configuration->getCustomNumericFunction('BARNUMERIC'));
	}

	/**
	 * @test
	 */
	public function dqlCustomDateTimeFunctionCanCorrectlyBeAppliedToConfiguration() {
		$configuration = $this->buildAndPrepareDqlCustomStringConfiguration();

		$this->assertEquals('Some\Foo\DateTimeClass', $configuration->getCustomDatetimeFunction('FOODATETIME'));
		$this->assertEquals('Some\Bar\DateTimeClass', $configuration->getCustomDatetimeFunction('BARDATETIME'));
	}

	/**
	 * @return \Doctrine\ORM\Configuration
	 */
	protected function buildAndPrepareDqlCustomStringConfiguration() {
		$entityManagerFactory = $this->getAccessibleMock('TYPO3\Flow\Persistence\Doctrine\EntityManagerFactory', array('dummy'));
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
