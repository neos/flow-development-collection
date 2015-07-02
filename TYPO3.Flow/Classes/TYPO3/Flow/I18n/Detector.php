<?php
namespace TYPO3\Flow\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The Detector class provides methods for automatic locale detection
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Detector {

	/**
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @var \TYPO3\Flow\I18n\LocaleCollection
	 */
	protected $localeCollection;

	/**
	 * @param \TYPO3\Flow\I18n\Service $localizationService
	 * @return void
	 */
	public function injectLocalizationService(\TYPO3\Flow\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\Flow\I18n\LocaleCollection $localeCollection
	 * @return void
	 */
	public function injectLocaleCollection(\TYPO3\Flow\I18n\LocaleCollection $localeCollection) {
		$this->localeCollection = $localeCollection;
	}

	/**
	 * Returns best-matching Locale object based on the Accept-Language header
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $acceptLanguageHeader The Accept-Language HTTP header
	 * @return \TYPO3\Flow\I18n\Locale Best-matching existing Locale instance
	 * @api
	 */
	public function detectLocaleFromHttpHeader($acceptLanguageHeader) {
		$acceptableLanguages = \TYPO3\Flow\I18n\Utility::parseAcceptLanguageHeader($acceptLanguageHeader);

		if ($acceptableLanguages === FALSE) {
			return $this->localizationService->getConfiguration()->getDefaultLocale();
		}

		foreach ($acceptableLanguages as $languageIdentifier) {
			if ($languageIdentifier === '*') {
				return $this->localizationService->getConfiguration()->getDefaultLocale();
			}

			try {
				$locale = new \TYPO3\Flow\I18n\Locale($languageIdentifier);
			} catch (\TYPO3\Flow\I18n\Exception\InvalidLocaleIdentifierException $exception) {
				continue;
			}

			$bestMatchingLocale = $this->localeCollection->findBestMatchingLocale($locale);

			if ($bestMatchingLocale !== NULL) {
				return $bestMatchingLocale;
			}
		}

		return $this->localizationService->getConfiguration()->getDefaultLocale();
	}

	/**
	 * Returns best-matching Locale object based on the locale identifier
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $localeIdentifier The locale identifier as used in Locale class
	 * @return \TYPO3\Flow\I18n\Locale Best-matching existing Locale instance
	 * @api
	 */
	public function detectLocaleFromLocaleTag($localeIdentifier) {
		try {
			return $this->detectLocaleFromTemplateLocale(new \TYPO3\Flow\I18n\Locale($localeIdentifier));
		} catch (\TYPO3\Flow\I18n\Exception\InvalidLocaleIdentifierException $e) {
			return $this->localizationService->getConfiguration()->getDefaultLocale();
		}
	}

	/**
	 * Returns best-matching Locale object based on the template Locale object
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale The template Locale object
	 * @return \TYPO3\Flow\I18n\Locale Best-matching existing Locale instance
	 * @api
	 */
	public function detectLocaleFromTemplateLocale(\TYPO3\Flow\I18n\Locale $locale) {
		$bestMatchingLocale = $this->localeCollection->findBestMatchingLocale($locale);

		if ($bestMatchingLocale !== NULL) {
			return $bestMatchingLocale;
		}

		return $this->localizationService->getConfiguration()->getDefaultLocale();
	}
}
