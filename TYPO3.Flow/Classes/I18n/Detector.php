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
 * The Detector class provides methods for automatic locale detection
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Detector {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @var \F3\FLOW3\I18n\LocaleCollection
	 */
	protected $localeCollection;

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \F3\FLOW3\I18n\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\F3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
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
	 * Returns best-matching Locale object based on the Accept-Language header
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $acceptLanguageHeader The Accept-Language HTTP header
	 * @return \F3\FLOW3\I18n\Locale Best-matching existing Locale instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function detectLocaleFromHttpHeader($acceptLanguageHeader) {
		$acceptableLanguages = \F3\FLOW3\I18n\Utility::parseAcceptLanguageHeader($acceptLanguageHeader);

		if ($acceptableLanguages === FALSE) {
			return $this->localizationService->getDefaultLocale();
		}

		foreach ($acceptableLanguages as $languageIdentifier) {
			if ($languageIdentifier === '*') {
				return $this->localizationService->getDefaultLocale();
			}

			try {
				$locale = $this->objectManager->create('F3\FLOW3\I18n\Locale', $languageIdentifier);
			} catch (\F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
				continue;
			}

			$bestMatchingLocale = $this->localeCollection->findBestMatchingLocale($locale);

			if ($bestMatchingLocale !== NULL) {
				return $bestMatchingLocale;
			}
		}

		return $this->localizationService->getDefaultLocale();
	}

	/**
	 * Returns best-matching Locale object based on the locale identifier
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $localeIdentifier The locale identifier as used in Locale class
	 * @return \F3\FLOW3\I18n\Locale Best-matching existing Locale instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function detectLocaleFromLocaleTag($localeIdentifier) {
		try {
			return $this->detectLocaleFromTemplateLocale($this->objectManager->create('F3\FLOW3\I18n\Locale', $localeIdentifier));
		} catch (\F3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $e) {
			return $this->localizationService->getDefaultLocale();
		}
	}

	/**
	 * Returns best-matching Locale object based on the template Locale object
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param \F3\FLOW3\I18n\Locale $locale The template Locale object
	 * @return \F3\FLOW3\I18n\Locale Best-matching existing Locale instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function detectLocaleFromTemplateLocale(\F3\FLOW3\I18n\Locale $locale) {
		$bestMatchingLocale = $this->localeCollection->findBestMatchingLocale($locale);

		if ($bestMatchingLocale !== NULL) {
			return $bestMatchingLocale;
		}

		return $this->localizationService->getDefaultLocale();
	}
}

?>