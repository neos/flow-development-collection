<?php
namespace Neos\Flow\I18n;

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\Files;

/**
 * A Service which provides further information about a given locale
 * and the current state of the i18n and L10n components.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Service
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * A collection of Locale objects representing currently installed locales,
     * in a hierarchical manner.
     *
     * @Flow\Inject(lazy=false)
     * @var LocaleCollection
     */
    protected $localeCollection;

    /**
     * @Flow\Inject(lazy=false)
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * The base path to use in filesystem operations. It is changed only in tests.
     *
     * @var string
     */
    protected $localeBasePath = 'resource://';

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings['i18n'];
    }

    /**
     * Initializes the locale service
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->configuration = new Configuration($this->settings['defaultLocale']);
        $this->configuration->setFallbackRule($this->settings['fallbackRule']);

        if ($this->cache->has('availableLocales')) {
            $this->localeCollection = $this->cache->get('availableLocales');
        } else {
            $this->generateAvailableLocalesCollectionByScanningFilesystem();
            $this->cache->set('availableLocales', $this->localeCollection);
        }
    }

    /**
     * @return Configuration
     * @api
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Returns the path to the existing localized version of file given.
     *
     * Searching is done for the current locale if no $locale parameter is
     * provided. The search is done according to the configured fallback
     * rule.
     *
     * If parameter $strict is provided, searching is done only for the
     * provided / current locale (without searching of files localized for
     * more generic locales).
     *
     * If no localized version of file is found, $filepath is returned without
     * any change.
     *
     * @param string $pathAndFilename Path to the file
     * @param Locale $locale Desired locale of localized file
     * @param boolean $strict Whether to match only provided locale (TRUE) or search for best-matching locale (FALSE)
     * @return array Path to the localized file (or $filename when no localized file was found) and the matched locale
     * @see Configuration::setFallbackRule()
     * @api
     */
    public function getLocalizedFilename($pathAndFilename, Locale $locale = null, $strict = false)
    {
        if ($locale === null) {
            $locale = $this->configuration->getCurrentLocale();
        }

        $filename = basename($pathAndFilename);
        if ((strpos($filename, '.')) !== false) {
            $dotPosition = strrpos($pathAndFilename, '.');
            $pathAndFilenameWithoutExtension = substr($pathAndFilename, 0, $dotPosition);
            $extension = substr($pathAndFilename, $dotPosition);
        } else {
            $pathAndFilenameWithoutExtension = $pathAndFilename;
            $extension = '';
        }

        if ($strict === true) {
            $possibleLocalizedFilename = $pathAndFilenameWithoutExtension . '.' . (string)$locale . $extension;
            if (file_exists($possibleLocalizedFilename)) {
                return [$possibleLocalizedFilename, $locale];
            }
        } else {
            foreach ($this->getLocaleChain($locale) as $localeIdentifier => $locale) {
                $possibleLocalizedFilename = $pathAndFilenameWithoutExtension . '.' . $localeIdentifier . $extension;
                if (file_exists($possibleLocalizedFilename)) {
                    return [$possibleLocalizedFilename, $locale];
                }
            }
        }
        return [$pathAndFilename, $locale];
    }

    /**
     * Returns the path to the existing localized version of file given.
     *
     * Searching is done for the current locale if no $locale parameter is
     * provided. The search is done according to the configured fallback
     * rule.
     *
     * If parameter $strict is provided, searching is done only for the
     * provided / current locale (without searching of files localized for
     * more generic locales).
     *
     * If no localized version of file is found, $filepath is returned without
     * any change.
     *
     * @param string $path Base directory to the translation files
     * @param string $sourceName name of the translation source
     * @param Locale $locale Desired locale of XLIFF file
     * @return array Path to the localized file (or $filename when no localized file was found) and the matched locale
     * @see Configuration::setFallbackRule()
     * @api
     */
    public function getXliffFilenameAndPath($path, $sourceName, Locale $locale = null)
    {
        if ($locale === null) {
            $locale = $this->configuration->getCurrentLocale();
        }

        foreach ($this->getLocaleChain($locale) as $localeIdentifier => $locale) {
            $possibleXliffFilename = Files::concatenatePaths([$path, $localeIdentifier, $sourceName . '.xlf']);
            if (file_exists($possibleXliffFilename)) {
                return [$possibleXliffFilename, $locale];
            }
        }
        return [false, $locale];
    }

    /**
     * Build a chain of locale objects according to the fallback rule and
     * the available locales.
     * @param Locale $locale
     * @return array
     */
    public function getLocaleChain(Locale $locale)
    {
        $fallbackRule = $this->configuration->getFallbackRule();
        $localeChain = [(string)$locale => $locale];

        if ($fallbackRule['strict'] === true) {
            foreach ($fallbackRule['order'] as $localeIdentifier) {
                $localeChain[$localeIdentifier] = new Locale($localeIdentifier);
            }
        } else {
            $locale = $this->findBestMatchingLocale($locale);
            while ($locale !== null) {
                $localeChain[(string)$locale] = $locale;
                $locale = $this->getParentLocaleOf($locale);
            }
            foreach ($fallbackRule['order'] as $localeIdentifier) {
                $locale = new Locale($localeIdentifier);
                $locale = $this->findBestMatchingLocale($locale);
                while ($locale !== null) {
                    $localeChain[(string)$locale] = $locale;
                    $locale = $this->getParentLocaleOf($locale);
                }
            }
        }
        $locale = $this->configuration->getDefaultLocale();
        $localeChain[(string)$locale] = $locale;

        return $localeChain;
    }

    /**
     * Returns a parent Locale object of the locale provided.
     *
     * @param Locale $locale The Locale to search parent for
     * @return Locale Existing Locale instance or NULL on failure
     * @api
     */
    public function getParentLocaleOf(Locale $locale)
    {
        return $this->localeCollection->getParentLocaleOf($locale);
    }

    /**
     * Returns Locale object which is the most similar to the "template" Locale
     * object given as parameter, from the collection of locales available in
     * the current Flow installation.
     *
     * @param Locale $locale The "template" Locale to be matched
     * @return mixed Existing Locale instance on success, NULL on failure
     * @api
     */
    public function findBestMatchingLocale(Locale $locale)
    {
        return $this->localeCollection->findBestMatchingLocale($locale);
    }

    /**
     * Returns a regex pattern including enclosing characters, that matches any of the configured
     * blacklist paths inside "Neos.Flow.i18n.scan.excludePatterns".
     *
     * @return string The regex pattern matching the configured blacklist
     */
    protected function getScanBlacklistPattern()
    {
        $pattern = implode('|', array_keys(array_filter((array)$this->settings['scan']['excludePatterns'])));
        if ($pattern !== '') {
            $pattern = '#' . str_replace('#', '\#', $pattern) . '#';
        }
        return $pattern;
    }

    /**
     * Finds all Locale objects representing locales available in the
     * Flow installation. This is done by scanning all Private and Public
     * resource files of all active packages, in order to find localized files.
     *
     * Localized files have a locale identifier added before their extension
     * (or at the end of filename, if no extension exists). For example, a
     * localized file for foobar.png, can be foobar.en.png, foobar.en_GB.png, etc.
     *
     * Also, all folder names inside '/Private/Translations' are scanned for valid locales.
     *
     * Just one localized resource file causes the corresponding locale to be
     * regarded as available (installed, supported).
     *
     * Note: result of this method invocation is cached
     *
     * @return void
     */
    protected function generateAvailableLocalesCollectionByScanningFilesystem()
    {
        $whitelistPaths = array_keys(array_filter((array)$this->settings['scan']['includePaths']));
        if ($whitelistPaths === []) {
            return;
        }
        $blacklistPattern = $this->getScanBlacklistPattern();

        /** @var PackageInterface $activePackage */
        foreach ($this->packageManager->getActivePackages() as $activePackage) {
            $packageResourcesPath = Files::getNormalizedPath($activePackage->getResourcesPath());

            if (!is_dir($packageResourcesPath)) {
                continue;
            }

            $directories = [];
            foreach ($whitelistPaths as $path) {
                $scanPath = Files::concatenatePaths(array($packageResourcesPath, $path));
                if (is_dir($scanPath)) {
                    array_push($directories, Files::getNormalizedPath($scanPath));
                }
            }

            while ($directories !== []) {
                $currentDirectory = array_pop($directories);
                $relativeDirectory = '/' . str_replace($packageResourcesPath, '', $currentDirectory);
                if ($blacklistPattern !== '' && preg_match($blacklistPattern, $relativeDirectory) === 1) {
                    continue;
                }

                if (stripos($currentDirectory, '/Private/Translations/') !== false) {
                    $localeIdentifier = Utility::extractLocaleTagFromDirectory($currentDirectory);
                    if ($localeIdentifier !== false) {
                        $this->localeCollection->addLocale(new Locale($localeIdentifier));
                    }
                }
                if ($handle = opendir($currentDirectory)) {
                    while (false !== ($filename = readdir($handle))) {
                        if ($filename[0] === '.') {
                            continue;
                        }
                        $pathAndFilename = Files::concatenatePaths([$currentDirectory, $filename]);
                        if (is_dir($pathAndFilename)) {
                            array_push($directories, Files::getNormalizedPath($pathAndFilename));
                        } else {
                            $localeIdentifier = Utility::extractLocaleTagFromFilename($filename);
                            if ($localeIdentifier !== false) {
                                $this->localeCollection->addLocale(new Locale($localeIdentifier));
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        }
    }
}
