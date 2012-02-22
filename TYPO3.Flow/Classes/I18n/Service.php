<?php
namespace TYPO3\FLOW3\I18n;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A Service which provides further information about a given locale
 * and the current state of the i18n and L10n components.
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class Service {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @var \TYPO3\FLOW3\I18n\LocaleCollection
	 */
	protected $localeCollection;

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @var \TYPO3\FLOW3\I18n\Configuration
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
	public function injectSettings(array $settings) {
		$this->settings = $settings['i18n'];
	}

	/**
	 * @param \TYPO3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\LocaleCollection $localeCollection
	 * @return void
	 */
	public function injectLocaleCollection(\TYPO3\FLOW3\I18n\LocaleCollection $localeCollection) {
		$this->localeCollection = $localeCollection;
	}

	/**
	 * Injects the FLOW3_I18n_Service cache
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this locale service
	 *
	 * @return void
	 */
	public function initialize() {
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
	 * @return \TYPO3\FLOW3\I18n\Configuration
	 * @api
	 */
	public function getConfiguration() {
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
	 * @param string $filename Path to the file
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Desired locale of localized file
	 * @param boolean $strict Whether to match only provided locale (TRUE) or search for best-matching locale (FALSE)
	 * @return string Path to the localized file, or $filename when no localized file was found
	 * @see Configuration::setFallbackRule()
	 * @api
	 */
	public function getLocalizedFilename($filename, Locale $locale = NULL, $strict = FALSE) {
		if ($locale === NULL) {
			$locale = $this->configuration->getCurrentLocale();
		}

		if (($dotPosition = strrpos($filename, '.')) !== FALSE) {
			$filenameWithoutExtension = substr($filename, 0, $dotPosition);
			$extension = substr($filename, $dotPosition);
		} else {
			$filenameWithoutExtension = $filename;
			$extension = '';
		}

		if ($strict === TRUE) {
			$possibleLocalizedFilename = $filenameWithoutExtension . '.' . (string)$locale . $extension;
			if (file_exists($possibleLocalizedFilename)) {
				return $possibleLocalizedFilename;
			}
		} else {
			foreach ($this->getLocaleChain($locale) as $localeIdentifier => $locale) {
				$possibleLocalizedFilename = $filenameWithoutExtension . '.' . $localeIdentifier . $extension;
				if (file_exists($possibleLocalizedFilename)) {
					return $possibleLocalizedFilename;
				}
			}
		}
		return $filename;

	/**
	 * Build a chain of locale objects according to the fallback rule and
	 * the available locales.
	 * @param \TYPO3\FLOW3\I18n\Locale $locale
	 * @return array
	 */
	public function getLocaleChain(Locale $locale) {
		$fallBackRule = $this->configuration->getFallbackRule();
		$localeChain = array((string)$locale => $locale);

		if ($fallBackRule['strict'] === TRUE) {
			foreach ($fallBackRule['order'] as $localeIdentifier) {
				$localeChain[$localeIdentifier] = new Locale($localeIdentifier);
			}
		} else {
			$locale = $this->findBestMatchingLocale($locale);
			while ($locale !== NULL) {
				$localeChain[(string)$locale] = $locale;
				$locale = $this->getParentLocaleOf($locale);
			}
			foreach ($fallBackRule['order'] as $localeIdentifier) {
				$locale = new Locale($localeIdentifier);
				$locale = $this->findBestMatchingLocale($locale);
				while ($locale !== NULL) {
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
	 * @param \TYPO3\FLOW3\I18n\Locale $locale The Locale to search parent for
	 * @return \TYPO3\FLOW3\I18n\Locale Existing \TYPO3\FLOW3\I18n\Locale instance or NULL on failure
	 * @api
	 */
	public function getParentLocaleOf(\TYPO3\FLOW3\I18n\Locale $locale) {
		return $this->localeCollection->getParentLocaleOf($locale);
	}

	/**
	 * Returns Locale object which is the most similar to the "template" Locale
	 * object given as parameter, from the collection of locales available in
	 * the current FLOW3 installation.
	 *
	 * @param \TYPO3\FLOW3\I18n\Locale $locale The "template" Locale to be matched
	 * @return mixed Existing \TYPO3\FLOW3\I18n\Locale instance on success, NULL on failure
	 * @api
	 */
	public function findBestMatchingLocale(\TYPO3\FLOW3\I18n\Locale $locale) {
		return $this->localeCollection->findBestMatchingLocale($locale);
	}

	/**
	 * Finds all Locale objects representing locales available in the
	 * FLOW3 installation. This is done by scanning all Private and Public
	 * resource files of all active packages, in order to find localized files.
	 *
	 * Localized files have a locale identifier added before their extension
	 * (or at the end of filename, if no extension exists). For example, a
	 * localized file for foobar.png, can be foobar.en.png, fobar.en_GB.png, etc.
	 *
	 * Just one localized resource file causes the corresponding locale to be
	 * regarded as available (installed, supported).
	 *
	 * Note: result of this method invocation is cached
	 *
	 * @return void
	 */
	protected function generateAvailableLocalesCollectionByScanningFilesystem() {
		foreach ($this->packageManager->getActivePackages() as $activePackage) {

			if (!is_dir($this->localeBasePath . $activePackage->getPackageKey() . '/')) continue;

			$directoryIterator = new \RecursiveDirectoryIterator($this->localeBasePath . $activePackage->getPackageKey() . '/', \RecursiveDirectoryIterator::UNIX_PATHS);
			$recursiveIteratorIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

			foreach ($recursiveIteratorIterator as $fileOrDirectory) {
				if ($fileOrDirectory->isFile()) {
					$localeIdentifier = Utility::extractLocaleTagFromFilename($fileOrDirectory->getPathName());

					if ($localeIdentifier !== FALSE && preg_match(Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $localeIdentifier) === 1) {
						$this->localeCollection->addLocale(new Locale($localeIdentifier));
					}
				}
			}
		}
	}

}

?>