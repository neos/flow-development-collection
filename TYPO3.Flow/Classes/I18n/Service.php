<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n;

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
 * A Service which provides further information about a given locale.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Service {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @var \F3\FLOW3\I18n\LocaleCollection
	 */
	protected $localeCollection;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * The base path to use in filesystem operations. It is changed only in tests.
	 *
	 * @var string
	 */
	protected $localeBasePath = 'resource://';

	/**
	 * @param array $settings
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \F3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectPackageManager(\F3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \F3\FLOW3\I18n\LocaleCollection $localeCollection
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocaleCollection(\F3\FLOW3\I18n\LocaleCollection $localeCollection) {
		$this->localeCollection = $localeCollection;
	}

	/**
	 * Injects the FLOW3_I18n_Service cache
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this locale service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function initialize() {
		try {
			$this->settings['locale']['defaultLocale'] = $this->objectManager->create('F3\FLOW3\I18n\Locale', $this->settings['locale']['defaultLocaleIdentifier']);
		} catch (\F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
			throw new \F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException('Default locale identifier set in the configuration is invalid.', 1280935191);
		}

		if ($this->cache->has('availableLocales')) {
			$this->localeCollection = $this->cache->get('availableLocales');
		} else {
			$this->generateAvailableLocalesCollectionByScanningFilesystem();
			$this->cache->set('availableLocales', $this->localeCollection);
		}
	}

	/**
	 * Returns the default Locale object for this FLOW3 installation.
	 *
	 * @return \F3\FLOW3\I18n\Locale The default Locale instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function getDefaultLocale() {
		return $this->settings['locale']['defaultLocale'];
	}

	/**
	 * Returns the path to the existing localized version of file given.
	 *
	 * Searching is done for the default locale if no $locale parameter is
	 * provided. If parameter $strict is provided, searching is done only for
	 * provided / default locale (without searching of files localized for
	 * more generic locales).
	 *
	 * If no localized version of file is found, $filepath is returned without
	 * any change.
	 *
	 * Note: This method assumes that provided file exists.
	 *
	 * @param string $filename Path to the file
	 * @param \F3\FLOW3\I18n\Locale $locale Desired locale of localized file
	 * @param bool $strict Whether to match only provided locale (TRUE) or search for best-matching locale (FALSE)
	 * @return string Path to the localized file, or $filename when no localized file was found
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function getLocalizedFilename($filename, \F3\FLOW3\I18n\Locale $locale = NULL, $strict = FALSE) {
		if ($locale === NULL) {
			$locale = $this->getDefaultLocale();
		}

		if (strrpos($filename, '.') !== FALSE) {
			$filenameWithoutExtension = substr($filename, 0, strrpos($filename, '.'));
			$extension = substr($filename, strrpos($filename, '.'));
		} else {
			$filenameWithoutExtension = $filename;
			$extension = '';
		}

		if ($strict === TRUE) {
			$possibleLocalizedFilename = $filenameWithoutExtension . '.' . (string)$locale . $extension;

			if (file_exists($possibleLocalizedFilename)) {
				return $possibleLocalizedFilename;
			} else {
				return $filename;
			}
		}

		$locale = $this->localeCollection->findBestMatchingLocale($locale);

		while ($locale !== NULL) {
			$possibleLocalizedFilename = $filenameWithoutExtension . '.' . (string)$locale . $extension;

			if (file_exists($possibleLocalizedFilename)) {
				return $possibleLocalizedFilename;
			}

			$locale = $this->localeCollection->getParentLocaleOf($locale);
		}

		return $filename;
	}

	/**
	 * Returns a parent Locale object of the locale provided.
	 *
	 * @param \F3\FLOW3\I18n\Locale $locale The Locale to search parent for
	 * @return mixed Existing \F3\FLOW3\I18n\Locale instance or NULL on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function getParentLocaleOf(\F3\FLOW3\I18n\Locale $locale) {
		return $this->localeCollection->getParentLocaleOf($locale);
	}

	/**
	 * Returns Locale object which is the most similar to the "template" Locale
	 * object given as parameter, from the collection of locales available in
	 * the current FLOW3 installation.
	 *
	 * @param \F3\FLOW3\I18n\Locale $locale The "template" Locale to be matched
	 * @return mixed Existing \F3\FLOW3\I18n\Locale instance on success, NULL on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function findBestMatchingLocale(\F3\FLOW3\I18n\Locale $locale) {
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
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function generateAvailableLocalesCollectionByScanningFilesystem() {
		foreach ($this->packageManager->getActivePackages() as $activePackage) {

			if (!is_dir($this->localeBasePath . $activePackage->getPackageKey() . '/')) continue;

			$directoryIterator = new \RecursiveDirectoryIterator($this->localeBasePath . $activePackage->getPackageKey() . '/', \RecursiveDirectoryIterator::UNIX_PATHS);
			$recursiveIteratorIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

			foreach ($recursiveIteratorIterator as $fileOrDirectory) {
				if ($fileOrDirectory->isFile()) {
					$localeIdentifier = \F3\FLOW3\I18n\Utility::extractLocaleTagFromFilename($fileOrDirectory->getPathName());

					if ($localeIdentifier !== FALSE) {
						try {
							$locale = $this->objectManager->create('F3\FLOW3\I18n\Locale', $localeIdentifier);
							$this->localeCollection->addLocale($locale);
						} catch (\F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
								// Just ignore current file and proceed
						}
					}
				}
			}
		}
	}
}

?>