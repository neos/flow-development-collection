<?php
namespace TYPO3\FLOW3\Tests\Unit\AOP\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Pointcut Setting Filter
 *
 */
class PointcutSettingFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesOnConfigurationSettingSetToTrue() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = TRUE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesOnConfigurationSettingSetToFalse() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = FALSE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertFalse($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException TYPO3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 */
	public function filterThrowsAnExceptionForNotExistingConfigurationSetting() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = TRUE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.foozy.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterDoesNotMatchOnConfigurationSettingThatIsNotBoolean() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'not boolean';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertFalse($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterCanHandleMissingSpacesInTheConfigurationSettingPath() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = TRUE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesOnAConditionSetInSingleQuotes() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'option value';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = \'option value\'');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesOnAConditionSetInDoubleQuotes() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'option value';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = "option value"');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertTrue($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterDoesNotMatchOnAFalseCondition() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'some other value';
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = \'some value\'');
		$filter->injectConfigurationManager($mockConfigurationManager);
		$this->assertFalse($filter->matches('', '', '', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException TYPO3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 */
	public function filterThrowsAnExceptionForAnIncorectCondition() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$settings['foo']['bar']['baz']['value'] = 'option value';

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = "forgot to close quotes');
		$filter->injectConfigurationManager($mockConfigurationManager);
	}
}
?>