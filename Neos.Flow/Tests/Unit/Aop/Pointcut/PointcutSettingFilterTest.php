<?php

declare(strict_types=1);

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
use Neos\Flow\Aop\Exception\InvalidPointcutExpressionException;
use Neos\Flow\Aop\Pointcut\PointcutSettingFilter;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Pointcut Setting Filter
 */
final class PointcutSettingFilterTest extends UnitTestCase
{
    #[Test]
    public function filterMatchesOnConfigurationSettingSetToTrue()
    {
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = true;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        self::assertTrue($filter->matches('', '', '', 1));
    }

    #[Test]
    public function filterMatchesOnConfigurationSettingSetToFalse()
    {
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = false;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        self::assertFalse($filter->matches('', '', '', 1));
    }

    #[Test]
    public function filterThrowsAnExceptionForNotExistingConfigurationSetting()
    {
        $this->expectException(InvalidPointcutExpressionException::class);
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = true;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.foozy.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
    }

    #[Test]
    public function filterDoesNotMatchOnConfigurationSettingThatIsNotBoolean()
    {
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = 'not boolean';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        self::assertFalse($filter->matches('', '', '', 1));
    }

    #[Test]
    public function filterCanHandleMissingSpacesInTheConfigurationSettingPath()
    {
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = true;
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value');
        $filter->injectConfigurationManager($mockConfigurationManager);
        self::assertTrue($filter->matches('', '', '', 1));
    }

    #[Test]
    public function filterMatchesOnAConditionSetInSingleQuotes()
    {
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = 'option value';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value = \'option value\'');
        $filter->injectConfigurationManager($mockConfigurationManager);
        self::assertTrue($filter->matches('', '', '', 1));
    }

    #[Test]
    public function filterMatchesOnAConditionSetInDoubleQuotes()
    {
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = 'option value';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value = "option value"');
        $filter->injectConfigurationManager($mockConfigurationManager);
        self::assertTrue($filter->matches('', '', '', 1));
    }

    #[Test]
    public function filterDoesNotMatchOnAFalseCondition()
    {
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = 'some other value';
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'package')->willReturn(($settings));

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value = \'some value\'');
        $filter->injectConfigurationManager($mockConfigurationManager);
        self::assertFalse($filter->matches('', '', '', 1));
    }

    #[Test]
    public function filterThrowsAnExceptionForAnIncorectCondition()
    {
        $this->expectException(InvalidPointcutExpressionException::class);
        $mockConfigurationManager = $this->createStub(ConfigurationManager::class);

        $settings['foo']['bar']['baz']['value'] = 'option value';

        $filter = new PointcutSettingFilter('package.foo.bar.baz.value = "forgot to close quotes');
        $filter->injectConfigurationManager($mockConfigurationManager);
    }
}
