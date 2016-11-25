<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A class for testing setting injection
 */
class ClassWithInjectedConfiguration
{
    /**
     * @Flow\InjectConfiguration(path="some.nonExisting.setting")
     * @var string
     */
    protected $nonExistingSetting = 'defaultValue';

    /**
     * @Flow\InjectConfiguration(path="tests.functional.settingInjection.someSetting")
     * @var string
     */
    protected $injectedSettingA;

    /**
     * @Flow\InjectConfiguration(path="tests.functional.settingInjection.someSetting", package="Neos.Flow")
     * @var string
     */
    protected $injectedSettingB;

    /**
     * @Flow\InjectConfiguration(path="tests.functional.settingInjection.someSetting", package="Neos.Flow")
     * @var string
     */
    protected $injectedSettingWithSetter;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array
     */
    protected $injectedSpecifiedPackageSettings;

    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $injectedCurrentPackageSettings;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @Flow\InjectConfiguration(type="Views")
     * @var array
     */
    protected $injectedViewsConfiguration;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return string
     */
    public function getNonExistingSetting()
    {
        return $this->nonExistingSetting;
    }

    /**
     * @return string
     */
    public function getInjectedSettingA()
    {
        return $this->injectedSettingA;
    }

    /**
     * @return string
     */
    public function getInjectedSettingB()
    {
        return $this->injectedSettingB;
    }

    /**
     * @return string
     */
    public function getInjectedSettingWithSetter()
    {
        return $this->injectedSettingWithSetter;
    }

    /**
     * @param string $injectedSettingWithSetter
     * @return void
     */
    public function setInjectedSettingWithSetter($injectedSettingWithSetter)
    {
        $this->injectedSettingWithSetter = strtoupper($injectedSettingWithSetter);
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return array
     */
    public function getInjectedSpecifiedPackageSettings()
    {
        return $this->injectedSpecifiedPackageSettings;
    }

    /**
     * @return array
     */
    public function getInjectedCurrentPackageSettings()
    {
        return $this->injectedCurrentPackageSettings;
    }

    /**
     * @return array
     */
    public function getInjectedViewsConfiguration()
    {
        return $this->injectedViewsConfiguration;
    }
}
