<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @var \F3\FLOW3\Locale\LocaleCollectionInterface
	 */
	protected $localeCollection;

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
	 * @param \F3\FLOW3\Locale\LocaleCollectionInterface $localeCollection
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocaleCollection(\F3\FLOW3\Locale\LocaleCollectionInterface $localeCollection) {
		$this->localeCollection = $localeCollection;
	}

	/**
	 * Initializes this locale service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo catch exception if locale identifier is invalid?
	 */
	public function initialize() {
		$locale = $this->objectManager->create('F3\FLOW3\Locale\Locale', $this->settings['locale']['defaultLocaleIdentifier']);
		$this->settings['locale']['defaultLocale'] = $locale;
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
	 * Returns the path to the existing localized version of file given.
	 * Searching is done for the default locale if no $locale parameter is
	 * provided. If parameter $strict is provided, searching is done only for
	 * provided / default locale (without searching of files localized for
	 * more generic locales.
	 * 
	 * If no localized version of file is found, $filepath is returned without
	 * any change.
	 * 
	 * Note: This method assumes that provided file exists.
	 *
	 * @param string $filename Path to the file
	 * @param \F3\FLOW3\Locale\Locale $locale Desired locale of localized file
	 * @param bool $strict Whether match only provided locale (or search for best-matching locale)
	 * @return string Path to the localized file, or $filepath
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getLocalizedFilename($filename, \F3\FLOW3\Locale\Locale $locale = NULL, $strict = FALSE) {
		if ($locale === NULL) {
			$locale = $this->getDefaultLocale();
		}		

		if (strrpos($filename, '.') !== FALSE) {
			$nameWithoutExtension = substr($filename, 0, strrpos($filename, '.'));
			$extension = substr($filename, strrpos($filename, '.'));
		} else {
			$nameWithoutExtension = $filename;
			$extension = '';
		}

		$locale = $this->localeCollection->findBestMatchingLocale($locale);

		if ($locale === NULL) {
			return $filename;
		}

		do {
			$possibleLocalizedFilename = $nameWithoutExtension . '.' . (string)$locale . $extension;

			if (file_exists($possibleLocalizedFilename)) {
				return $possibleLocalizedFilename;
			}

			$locale = $this->localeCollection->getParentLocaleOf($locale);
		} while($locale !== NULL);

		return $filename;
	}
}

?>