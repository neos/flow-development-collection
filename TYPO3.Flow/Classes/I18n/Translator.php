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
 * A class for translating messages
 *
 * Messages (labels) can be translated in two modes:
 * - by original label: untranslated label is used as a key
 * - by ID: string identifier is used as a key (eg. user.noaccess)
 *
 * Correct plural form of translated message is returned when $quantity
 * parameter is provided to a method. Otherwise, or on failure just translated
 * version is returned (eg. when string is translated only to one form).
 *
 * When all fails, untranslated (original) string or ID is returned (depends on
 * translation method).
 *
 * Placeholders' resolving is done when needed (see FormatResolver class).
 *
 * Actual translating is done by injected TranslationProvider instance, so
 * storage format depends on concrete implementation.
 *
 * @FLOW3\Scope("singleton")
 * @api
 * @see \TYPO3\FLOW3\I18n\FormatResolver
 * @see \TYPO3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface
 * @see \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader
 */
class Translator {

	/**
	 * @var \TYPO3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @var \TYPO3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface
	 */
	protected $translationProvider;

	/**
	 * @var \TYPO3\FLOW3\I18n\FormatResolver
	 */
	protected $formatResolver;

	/**
	 * @var \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader
	 */
	protected $pluralsReader;

	/**
	 * @param \TYPO3\FLOW3\I18n\Service $localizationService
	 * @return void
	 */
	public function injectLocalizationService(\TYPO3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface $translationProvider
	 * @return void
	 */
	public function injectTranslationProvider(\TYPO3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface $translationProvider) {
		$this->translationProvider = $translationProvider;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\FormatResolver $formatResolver
	 * @return void
	 */
	public function injectFormatResolver(\TYPO3\FLOW3\I18n\FormatResolver $formatResolver) {
		$this->formatResolver = $formatResolver;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader
	 * @return void
	 */
	public function injectPluralsReader(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader) {
		$this->pluralsReader = $pluralsReader;
	}

	/**
	 * Translates message given as $originalLabel.
	 *
	 * Searches for translation in filename defined as $sourceName. It is a
	 * relative name (interpretation depends on concrete translation provider
	 * injected to this class).
	 *
	 * If any arguments are provided in $arguments array, they will be inserted
	 * to the translated string (in place of corresponding placeholders, with
	 * format defined by these placeholders).
	 *
	 * If $quantity is provided, correct plural form for provided $locale will
	 * be chosen and used to choose correct translation variant.
	 *
	 * If no $locale is provided, default system locale will be used.
	 *
	 * @param string $originalLabel Untranslated message
	 * @param array $arguments An array of values to replace placeholders with
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use (NULL for default one)
	 * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
	 * @param string $packageKey Key of the package containing the source file
	 * @return string Translated $originalLabel or $originalLabel itself on failure
	 * @api
	 */
	public function translateByOriginalLabel($originalLabel, array $arguments = array(), $quantity = NULL, \TYPO3\FLOW3\I18n\Locale $locale = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.FLOW3') {
		if ($locale === NULL) {
			$locale = $this->localizationService->getCurrentLocale();
		}

		if ($quantity === NULL) {
			$pluralForm = NULL;
		} else {
			$pluralForm = $this->pluralsReader->getPluralForm($quantity, $locale);
		}

		$translatedMessage = $this->translationProvider->getTranslationByOriginalLabel($originalLabel, $locale, $pluralForm, $sourceName, $packageKey);

		if ($translatedMessage === FALSE) {
				// Return original message if no translation available
			$translatedMessage = $originalLabel;
		}

		if (!empty($arguments)) {
			$translatedMessage = $this->formatResolver->resolvePlaceholders($translatedMessage, $arguments, $locale);
		}

		return $translatedMessage;
	}

	/**
	 * Returns translated string found under the key $labelId in $sourceName file.
	 *
	 * This method works same as translateByOriginalLabel(), except it uses
	 * ID, and not source message, as a key.
	 *
	 * @param string $labelId Key to use for finding translation
	 * @param array $arguments An array of values to replace placeholders with
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use (NULL for default one)
	 * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
	 * @param string $packageKey Key of the package containing the source file
	 * @return string Translated message or $labelId on failure
	 * @api
	 * @see \TYPO3\FLOW3\I18n\Translator::translateByOriginalLabel()
	 */
	public function translateById($labelId, array $arguments = array(), $quantity = NULL, \TYPO3\FLOW3\I18n\Locale $locale = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.FLOW3') {
		if ($locale === NULL) {
			$locale = $this->localizationService->getCurrentLocale();
		}

		if ($quantity === NULL) {
			$pluralForm = NULL;
		} else {
			$pluralForm = $this->pluralsReader->getPluralForm($quantity, $locale);
		}

		$translatedMessage = $this->translationProvider->getTranslationById($labelId, $locale, $pluralForm, $sourceName, $packageKey);

		if ($translatedMessage === FALSE) {
				// Return the ID if no translation available
			$translatedMessage = $labelId;
		} elseif (!empty($arguments)) {
			$translatedMessage = $this->formatResolver->resolvePlaceholders($translatedMessage, $arguments, $locale);
		}

		return $translatedMessage;
	}
}

?>