<?php
namespace TYPO3\FLOW3\I18n\TranslationProvider;

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
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 * @scope singleton
 */
class XliffTranslationProvider implements \TYPO3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface {

	/**
	 * An absolute path to the directory where translation files reside.
	 * It is changed only in tests.
	 *
	 * @var string
	 */
	protected $xliffBasePath = 'resource://TYPO3.FLOW3/Private/Locale/Translations/';

	/**
	 * @var \TYPO3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @var \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader
	 */
	protected $pluralsReader;

	/**
	 * A collection of models requested at least once in current request.
	 *
	 * This is an associative array with pairs as follow:
	 * ['filename'] => $model,
	 *
	 * @var array<\TYPO3\FLOW3\I18n\Xliff\XliffModel>
	 */
	protected $models;

	/**
	 * @param \TYPO3\FLOW3\I18n\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\TYPO3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectPluralsReader(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader) {
		$this->pluralsReader = $pluralsReader;
	}

	/**
	 * Returns translated label of $originalLabel from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $sourceName A relative path to the filename with translations (labels' catalog)
	 * @param string $originalLabel Label used as a key in order to find translation
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTranslationByOriginalLabel($sourceName, $originalLabel, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER) {
		$pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

		if (!in_array($pluralForm, $pluralFormsForProvidedLocale)) {
			throw new \TYPO3\FLOW3\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
		}

		$model = $this->getModel($sourceName, $locale);
			// We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
		$translation = $model->getTargetBySource($originalLabel, (int)array_search($pluralForm, $pluralFormsForProvidedLocale));

		return $translation;
	}

	/**
	 * Returns label for a key ($labelId) from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $sourceName A relative path to the filename with translations (labels' catalog)
	 * @param string $labelId Key used to find translated label
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTranslationById($sourceName, $labelId, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER) {
		$pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

		if (!in_array($pluralForm, $pluralFormsForProvidedLocale)) {
			throw new \TYPO3\FLOW3\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033387);
		}

		$model = $this->getModel($sourceName, $locale);
		$translation = $model->getTargetByTransUnitId($labelId, (int)array_search($pluralForm, $pluralFormsForProvidedLocale));

		return $translation;
	}

	/**
	 * Returns a XliffModel instance representing desired CLDR file.
	 *
	 * Will return existing instance if a model for given $sourceName was already
	 * requested before. Returns FALSE when $sourceName doesn't point to existing
	 * file.
	 *
	 * @param string $sourceName Relative path to existing CLDR file
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale object
	 * @return \TYPO3\FLOW3\I18n\Xliff\XliffModel New or existing instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function getModel($sourceName, \TYPO3\FLOW3\I18n\Locale $locale) {
		$sourceName = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->xliffBasePath, $sourceName . '.xlf'));
		$sourceName = $this->localizationService->getLocalizedFilename($sourceName, $locale);

		if (isset($this->models[$sourceName])) {
			return $this->models[$sourceName];
		}

		return $this->models[$sourceName] = new \TYPO3\FLOW3\I18n\Xliff\XliffModel($sourceName);
	}
}

?>