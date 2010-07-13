<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n;

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
 * A class for translating messages
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Translator {

	/**
	 * @var \F3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @var \F3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface
	 */
	protected $translationProvider;

	/**
	 * @var \F3\FLOW3\I18n\FormatResolver
	 */
	protected $formatResolver;

	/**
	 * @var \F3\FLOW3\I18n\Cldr\Reader\PluralsReader
	 */
	protected $pluralsReader;

	/**
	 * @param \F3\FLOW3\I18n\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\F3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \F3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface $translationProvider
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectTranslationProvider(\F3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface $translationProvider) {
		$this->translationProvider = $translationProvider;
	}

	/**
	 * @param \F3\FLOW3\I18n\FormatResolver $formatResolver
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectFormatResolver(\F3\FLOW3\I18n\FormatResolver $formatResolver) {
		$this->formatResolver = $formatResolver;
	}

	/**
	 * @param \F3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectPluralsReader(\F3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader) {
		$this->pluralsReader = $pluralsReader;
	}

	/**
	 * Translates message given as $originalLabel.
	 *
	 * Searches for translation in filename defined sa $source. It is a relative
	 * name (interpretation depends on concrete translation provider injected
	 * to this class).
	 *
	 * If any parameters are provided in $values array, they will be inserted to
	 * the translated string (in place of corresponding placeholders, with format
	 * defined by these placeholders).
	 *
	 * If $quantity is provided, correct plural form for provided $locale will
	 * be chosen and used to choose correct translation variant.
	 *
	 * If no $locale is provided, default system locale will be used.
	 * 
	 * @param string $originalLabel Untranslated message
	 * @param string $source Name of file with translations
	 * @param array $values An array of values to replace placeholders with
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use (NULL for default one)
	 * @return string Translated $originalLabel or $originalLabel itself on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function translateByOriginalLabel($originalLabel, $source, array $values = array(), $quantity = NULL, \F3\FLOW3\I18n\Locale $locale = NULL) {
		if ($locale === NULL) {
			$locale = $this->localizationService->getDefaultLocale();
		}

		if ($quantity === NULL) {
			$pluralForm = 'other';
		} else {
			$pluralForm = $this->pluralsReader->getPluralForm($quantity, $locale);
		}

		$translatedMessage = $this->translationProvider->getTranslationByOriginalLabel($source, $originalLabel, $locale, $pluralForm);

		if ($translatedMessage === FALSE) {
				// Return original message if no translation available
			$translatedMessage = $originalLabel;
		}

		if (!empty($values)) {
			$translatedMessage = $this->formatResolver->resolvePlaceholders($translatedMessage, $values, $locale);
		}

		return $translatedMessage;
	}

	/**
	 * Returns translated string found under the key $id in $source file.
	 *
	 * This method works same as translateByOriginalLabel(), except it uses
	 * ID, and not source message, as a key.
	 *
	 * @param string $id Key to use for finding translation
	 * @param string $source Name of file with translations
	 * @param array $values An array of values to replace placeholders with
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use (NULL for default one)
	 * @return string Translated message or $id on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 * @see \F3\FLOW3\I18n\Translator::translateByOriginalLabel()
	 */
	public function translateById($id, $source, array $values = array(), $quantity = NULL, \F3\FLOW3\I18n\Locale $locale = NULL) {
		if ($locale === NULL) {
			$locale = $this->localizationService->getDefaultLocale();
		}

		if ($quantity === NULL) {
			$pluralForm = 'other';
		} else {
			$pluralForm = $this->pluralsReader->getPluralForm($quantity, $locale);
		}

		$translatedMessage = $this->translationProvider->getTranslationById($source, $id, $locale, $pluralForm);

		if ($translatedMessage === FALSE) {
				// Return the ID if no translation available
			$translatedMessage = $id;
		} else if (!empty($values)) {
			$translatedMessage = $this->formatResolver->resolvePlaceholders($translatedMessage, $values, $locale);
		}

		return $translatedMessage;
	}
}

?>