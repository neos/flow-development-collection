<?php
namespace Neos\Flow\Tests\Unit\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Aop;

/**
 * Testcase for the Pointcut Setting Filter
 */
class PointcutSettingFilterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function filterMatchesOnConfigurationSettingSetToTrue()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = true;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        $this->assertTrue($filter->matches('', '', '', 1));
    }

    /**
     * @test
     */
    public function filterMatchesOnConfigurationSettingSetToFalse()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = false;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        $this->assertFalse($filter->matches('', '', '', 1));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function filterThrowsAnExceptionForNotExistingConfigurationSetting()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = true;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.foozy.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
    }

    /**
     * @test
     */
    public function filterDoesNotMatchOnConfigurationSettingThatIsNotBoolean()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = 'not boolean';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        $this->assertFalse($filter->matches('', '', '', 1));
    }

    /**
     * @test
     */
    public function filterCanHandleMissingSpacesInTheConfigurationSettingPath()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = true;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        $this->assertTrue($filter->matches('', '', '', 1));
    }

    /**
     * @test
     */
    public function filterMatchesOnAConditionSetInSingleQuotes()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = 'option value';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = \'option value\'');
        $filter->injectConfigurationManager($mockConfigurationManager);
        $this->assertTrue($filter->matches('', '', '', 1));
    }

    /**
     * @test
     */
    public function filterMatchesOnAConditionSetInDoubleQuotes()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = 'option value';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = "option value"');
        $filter->injectConfigurationManager($mockConfigurationManager);
        $this->assertTrue($filter->matches('', '', '', 1));
    }

    /**
     * @test
     */
    public function filterDoesNotMatchOnAFalseCondition()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = 'some other value';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->will($this->returnValue($settings));

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = \'some value\'');
        $filter->injectConfigurationManager($mockConfigurationManager);
        $this->assertFalse($filter->matches('', '', '', 1));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function filterThrowsAnExceptionForAnIncorectCondition()
    {
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $settings['foo']['bar']['baz']['value'] = 'option value';

        $filter = new Aop\Pointcut\PointcutSettingFilter('package.foo.bar.baz.value = "forgot to close quotes');
        $filter->injectConfigurationManager($mockConfigurationManager);
    }
}
