<?php
namespace TYPO3\Flow\I18n;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Package\Package;

/**
 * @Flow\Scope("singleton")
 */
class ServiceFactory
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Package\PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     */
    public function injectSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return \TYPO3\Flow\I18n\Service
     */
    public function create()
    {
        $localizationDirectories = array_map(function (Package $package) {
            return $package->getResourcesPath();
        }, $this->packageManager->getActivePackages());

        return new \TYPO3\Flow\I18n\Service($this->settings['i18n'], $localizationDirectories, $this->cacheManager->getCache('Flow_I18n_AvailableLocalesCache'));
    }
}