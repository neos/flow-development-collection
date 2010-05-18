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
	 * The FLOW3 settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * The base path to use in filesystem operations. It is changed only in tests.
	 *
	 * @var string
	 */
	protected $localeBasePath = 'package://';

	/**
	 * A tree of Locale objects representing currently installed locales, in a
	 * hierarchical manner.
	 *
	 * @var \F3\FLOW3\Locale\LocaleTreeInterface
	 */
	protected $availableLocalesTree;

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
	 * @param \F3\FLOW3\Locale\LocaleTreeInterface $packageManager
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocaleTree(\F3\FLOW3\Locale\LocaleTreeInterface $localeTree) {
		$this->availableLocalesTree = $localeTree;
	}

	/**
	 * Constructs the detector. Needs the objectManager to be injected before, as
	 * it generates a tree of available locales on start.
	 *
	 * @return void
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
			return $this->getDefaultLocale();
		}

		foreach ($acceptableLanguages as $tag) {
			if ($tag === '*') {
				return $this->getDefaultLocale();
			}

			try {
				$parsedLocale = $this->objectManager->create('F3\FLOW3\Locale\Locale', $tag);
			} catch (\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifierException $e) {
				continue;
			}

			$foundLocale = $this->availableLocalesTree->findBestMatchingLocale($parsedLocale);

			if ($foundLocale !== NULL) {
				return $foundLocale;
			}
		}

		return $this->getDefaultLocale();
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
			return $this->getDefaultLocale();
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
		$foundLocale = $this->availableLocalesTree->findBestMatchingLocale($locale);

		if ($foundLocale !== NULL) {
			return $foundLocale;
		}

		return $this->getDefaultLocale();
	}

	/**
	 * Returns the default Locale object for this FLOW3 installation.
	 *
	 * @return \F3\FLOW3\Locale\Locale
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getDefaultLocale() {
		return $this->settings['locale']['defaultLocale'];
	}

	/**
	 * Computes an tree of Locale objects representing locales available in the
	 * FLOW3 installation. This is done by scanning all Private/Locale and
	 * Public/Locale folders of all active packages, and searching for directories
	 * with names which seems to be locale identifiers ;-). Array is generated
	 * only once and then is saved in this class.
	 *
	 * Note: for now, CLDR directory in FLOW3 package will be interpreted as
	 * a valid locale. Please see #7720 for more information.
	 *
	 * Note: before this method is invoked one must ensure that availableLocalesTree
	 * is empty.
	 *
	 * @todo: some validation should be done here, ie whether the directory has any files
	 *
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function generateAvailableLocalesTreeByScanningFilesystem() {
		foreach ($this->packageManager->getActivePackages() as $activePackage) {
			foreach (array('Private', 'Public') as $resourceVisibility) {
				$localeDirectoryPath = $this->localeBasePath . $activePackage->getPackageKey() . '/' . $resourceVisibility . '/Locale/';

				if(!is_dir($localeDirectoryPath)) {
					continue;
				}

				$packageDirectoryIterator = new \DirectoryIterator($localeDirectoryPath);
				foreach ($packageDirectoryIterator as $subDirectory) {
					if (is_dir($localeDirectoryPath . $subDirectory) === TRUE) {
						try {
							$newLocale = $this->objectManager->create('F3\FLOW3\Locale\Locale', (string)$subDirectory);
								// Validation should be placed here
							$this->availableLocalesTree->addLocale($newLocale);
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