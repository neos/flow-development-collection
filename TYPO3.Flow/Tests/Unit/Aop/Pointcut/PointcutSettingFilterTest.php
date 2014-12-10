<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Pointcut;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Pointcut Setting Filter
 *
 */
class PointcutSettingFilterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function filterMatchesOnConfigurationSettingSetToTrue() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = TRUE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 */
	public function filterMatchesOnConfigurationSettingSetToFalse() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = FALSE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertFalse($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function filterThrowsAnExceptionForNotExistingConfigurationSetting() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = TRUE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.foozy.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
	}

	/**
	 * @test
	 */
	public function filterDoesNotMatchOnConfigurationSettingThatIsNotBoolean() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'not boolean';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertFalse($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 */
	public function filterCanHandleMissingSpacesInTheConfigurationSettingPath() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = TRUE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 */
	public function filterMatchesOnAConditionSetInSingleQuotes() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'option value';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = \'option value\'');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 */
	public function filterMatchesOnAConditionSetInDoubleQuotes() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'option value';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = "option value"');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 */
	public function filterDoesNotMatchOnAFalseCondition() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'some other value';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = \'some value\'');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertFalse($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function filterThrowsAnExceptionForAnIncorectCondition() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'option value';

		$filter = new \TYPO3\Flow\Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = "forgot to close quotes');
		$filter->injectConfigurationManager($mockConfigurationManager);
	}
}
