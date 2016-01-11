<?php
namespace Neos\RedirectHandler\Service;

/*
 * This file is part of the Neos.RedirectHandler package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Annotations as Flow;

/**
 * Settings Service
 *
 * @Flow\Scope("singleton")
 * @api
 */
class SettingsService
{
    const FEATURE_HIT_COUNTER = 'hitCounter';

    /**
     * @Flow\InjectConfiguration(package="Neos.RedirectHandler")
     * @var array
     */
    protected $settings;

    /**
     * @return integer
     */
    public function getRedirectStatusCode()
    {
        return isset($this->settings['statusCode']['redirect']) ? (integer)$this->settings['statusCode']['redirect'] : 301;
    }

    /**
     * @return integer
     */
    public function getGoneStatusCode()
    {
        return isset($this->settings['statusCode']['gone']) ? (integer)$this->settings['statusCode']['gone'] : 301;
    }

    /**
     * @param string $featureName
     * @return boolean
     */
    public function isFeatureEnabled($featureName)
    {
        return (isset($this->settings['features'][$featureName]) && $this->settings['features'][$featureName] === true);
    }
}
