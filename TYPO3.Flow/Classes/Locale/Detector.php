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
	 * @var array
	 */
	protected $settings;

	/**
	 * The 'protocol' to use in filesystem operations. It is changed only in tests.
	 * @var string
	 */
	protected $filesystemProtocol = 'package://';

	/**
	 * Array of Locale objects representing currently installed locales
	 * @var array
	 */
	static protected $availableLocales = array();

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
			return $this->settings['locale']['defaultLocale'];
		}

		$possibleLocale = NULL;

		foreach ($acceptableLanguages as $tag) {
			if ($tag === '*') {
				return $this->settings['locale']['defaultLocale'];
			}

				// Element 0 is a language code, element 1 is a region code
			$tagElements = explode('-', $tag);
			foreach ($this->getAvailableLocales() as $availableLocale) {
				if ($availableLocale->getLanguage() === $tagElements[0]) {
					if (!isset($tagElements[1])) {
						return $availableLocale;
					} elseif ($availableLocale->getRegion() === $tagElements[1]) {
						return $availableLocale;
					}

					   // Set possible locale in case we won't find better matching
					if ($possibleLocale === NULL) {
						$possibleLocale = $availableLocale;
					}
				}
			}
		}

		if ($possibleLocale !== NULL) {
			return $possibleLocale;
		}

		return $this->settings['locale']['defaultLocale'];
	}

	/**
	 * Returns best-matching Locale object based on the locale identifier
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * This method ranks installed locales in order to get the best match.
	 *
	 * @param string $tag The locale identifier as used in Locale class
	 * @return \F3\FLOW3\Locale\Locale
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromLocaleTag($tag) {
			// Parse the tag (this doesn't mean that exacly that locale exists in the system)
		$parsedTagLocale = $this->objectManager->create('F3\FLOW3\Locale\Locale', $tag);
		$rankOfBestMatch = 0;
		$indexOfBestMatch = NULL;

		foreach ($this->getAvailableLocales() as $index => $availableLocale) {
			$rank = 0;
			if ($parsedTagLocale->getLanguage() === $availableLocale->getLanguage()) {
				$rank = 10;

				if ($parsedTagLocale->getRegion() === $availableLocale->getRegion()) {
						// Regions are the same, increase the rank
					$rank += 3;
				} elseif ($parsedTagLocale->getRegion() !== NULL && $availableLocale->getRegion() !== NULL) {
						// Regions are totally different, decrease the rank
					$rank -= 7;
				}

				if ($parsedTagLocale->getScript() === $availableLocale->getScript()) {
					$rank += 5;
				} elseif ($parsedTagLocale->getScript() !== NULL && $availableLocale->getScript() !== NULL) {
					$rank -= 5;
				}

				if ($parsedTagLocale->getVariant() === $availableLocale->getVariant()) {
					$rank += 7;
				} elseif ($parsedTagLocale->getVariant() !== NULL && $availableLocale->getVariant() !== NULL) {
					$rank -= 3;
				}

				if ($rank > $rankOfBestMatch) {
					$rankOfBestMatch = $rank;
					$indexOfBestMatch = $index;
				}
			}
		}

		if ($indexOfBestMatch !== NULL) {
			$availableLocales = $this->getAvailableLocales();
			return $availableLocales[$indexOfBestMatch];
		}

		return $this->settings['locale']['defaultLocale'];
	}

	/**
	 * Returns an array of Locale objects describing locales available in
	 * current FLOW3 installation.
	 *
	 * @return array Array of \F3\FLOW3\Locale\Locale
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getAvailableLocales() {
		if (empty(self::$availableLocales) === FALSE) {
			return self::$availableLocales;
		}

		return $this->detectAvailableLocalesByScanningFilesystem();
	}

	/**
	 * Computes an array of Locale objects representing locales available in the
	 * FLOW3 installation. This is done by scanning all Private/Locale and
	 * Public/Locale folders of all active packages, and searching for directories
	 * with names which seems to be locale identifiers ;-). Array is generated
	 * only once and then is saved in this class.
	 *
	 * Note: for now, CLDR directory in FLOW3 package will be interpreted as
	 * a valid locale. Please see #7720 for more information.
	 *
	 * @todo: some validation should be done here, ie whether the directory has any files
	 *
	 * @return array Array of \F3\FLOW3\Locale\Locale instances
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function detectAvailableLocalesByScanningFilesystem() {
		foreach ($this->packageManager->getActivePackages() as $activePackage) {
			foreach (array('Private', 'Public') as $resourceVisibility) {
				$localeDirectoryPath = $this->filesystemProtocol . $activePackage->getPackageKey() . '/' . $resourceVisibility . '/Locale/';
				$packageDirectory = opendir($localeDirectoryPath);

				if($packageDirectory === FALSE) {
					continue;
				}

				while (($subdirectory = readdir($packageDirectory)) !== FALSE) {
					if (is_dir($localeDirectoryPath . $subdirectory) === TRUE) {
						try {
							self::$availableLocales[] = $this->objectManager->create('F3\FLOW3\Locale\Locale', $subdirectory);
								// Validation should be placed here
						} catch (\F3\FLOW3\Locale\Exception\InvalidLocaleIdentifierException $e) {
								// Just ignore current directory and proceed
						}
					}
				}

				self::$availableLocales = array_unique(self::$availableLocales);
				closedir($packageDirectory);
			}
		}

		return self::$availableLocales;
	}
}
?>