<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

/* *
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
 * The Detector class which provides methods for automatic locale detection
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Detector {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \F3\FLOW3\Locale\Service
	 */
	protected $localizationService;

	/**
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @var \F3\FLOW3\Locale\LocaleCollectionInterface
	 */
	protected $localeCollection;

	/**
	 * The base path to use in filesystem operations. It is changed only in tests.
	 *
	 * @var string
	 */
	protected $localeBasePath = 'package://';

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings The settings
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
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
	 * @param \F3\FLOW3\Locale\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\F3\FLOW3\Locale\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \F3\FLOW3\Locale\LocaleCollectionInterface $localeCollection
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocaleCollection(\F3\FLOW3\Locale\LocaleCollectionInterface $localeCollection) {
		$this->localeCollection = $localeCollection;
	}

	/**
	 * Constructs the detector. Needs the objectManager to be injected before, as
	 * it generates a collection of available locales on start.
	 *
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function initializeObject() {
		$this->generateAvailableLocalesTreeByScanningFilesystem();
	}

	/**
	 * Returns best-matching Locale object based on the Accept-Language header
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $header The Accept-Language HTTP header
	 * @return \F3\FLOW3\Locale\Locale
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromHttpHeader($header) {
		$acceptableLanguages = \F3\FLOW3\Locale\Utility::parseAcceptLanguageHeader($header);

		if ($acceptableLanguages === FALSE) {
			return $this->localizationService->getDefaultLocale();
		}

		foreach ($acceptableLanguages as $tag) {
			if ($tag === '*') {
				return $this->localizationService->getDefaultLocale();
			}

			try {
				$parsedLocale = $this->objectManager->create('F3\FLOW3\Locale\Locale', $tag);
			} catch (\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifierException $e) {
				continue;
			}

			$foundLocale = $this->localeCollection->findBestMatchingLocale($parsedLocale);

			if ($foundLocale !== NULL) {
				return $foundLocale;
			}
		}

		return $this->localizationService->getDefaultLocale();
	}

	/**
	 * Returns best-matching Locale object based on the locale identifier
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $tag The locale identifier as used in Locale class
	 * @return \F3\FLOW3\Locale\Locale
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromLocaleTag($tag) {
		try {
				// Parse the tag (this doesn't mean that exacly that locale exists in the system)
			return $this->detectLocaleFromTemplateLocale($this->objectManager->create('F3\FLOW3\Locale\Locale', $tag));
		} catch (\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifierException $e) {
			return $this->localizationService->getDefaultLocale();
		}
	}

	/**
	 * Returns best-matching Locale object based on the template Locale object
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale The template Locale object
	 * @return \F3\FLOW3\Locale\Locale
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromTemplateLocale($locale) {
		$foundLocale = $this->localeCollection->findBestMatchingLocale($locale);

		if ($foundLocale !== NULL) {
			return $foundLocale;
		}

		return $this->localizationService->getDefaultLocale();
	}

	/**
	 * Finds all Locale objects representing locales available in the
	 * FLOW3 installation. This is done by scanning all Private and Public
	 * resource files of all active packages, in order to find localized files.
	 *
	 * Localized files have a locale tag added before their extension (or at the
	 * end of filename, if no extension exists). For example, a localized file
	 * for foobar.png, can be foobar.en.png, fobar.en_GB.png, etc.
	 *
	 * Just one localized resource file causes the corresponding locale to be
	 * regarded as available (installed, supported).
	 *
	 * Note: this method is invoked only once per request
	 *
	 * @todo: cache result of this method between requests
	 *
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function generateAvailableLocalesTreeByScanningFilesystem() {
		foreach ($this->packageManager->getActivePackages() as $activePackage) {
			$directoryIterator = new \RecursiveDirectoryIterator($this->localeBasePath . $activePackage->getPackageKey() . '/');
			$recursiveIteratorIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

			foreach ($recursiveIteratorIterator as $fileOrDirectory) {
				if ($fileOrDirectory->isFile()) {
					$localeTag = \F3\FLOW3\Locale\Utility::extractLocaleTagFromFilename($fileOrDirectory->getPathName());

					if ($localeTag !== FALSE) {
						try {
							$locale = $this->objectManager->create('F3\FLOW3\Locale\Locale', $localeTag);
							$this->localeCollection->addLocale($locale);
						} catch (\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifierException $e) {
								// Just ignore current directory and proceed
						}
					}
				}
			}
		}
	}
}

?>