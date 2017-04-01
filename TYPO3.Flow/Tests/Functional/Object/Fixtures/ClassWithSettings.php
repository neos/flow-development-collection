<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class for testing setting injection
 */
class ClassWithSettings
{
    /**
     * @Flow\Inject(setting="some.nonExisting.setting")
     * @var string
     */
    protected $nonExistingSetting;

    /**
     * @Flow\Inject(setting="tests.functional.settingInjection.someSetting")
     * @var string
     */
    protected $injectedSettingA;

    /**
     * @Flow\Inject(setting="tests.functional.settingInjection.someSetting", Package="TYPO3.Flow")
     * @var string
     */
    protected $injectedSettingB;

    /**
     * @var array
     */
    protected $settings;

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
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }
}
