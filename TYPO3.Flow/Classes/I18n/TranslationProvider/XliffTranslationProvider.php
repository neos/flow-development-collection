<?php
namespace TYPO3\FLOW3\I18n\TranslationProvider;

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
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 * @FLOW3\Scope("singleton")
 */
class XliffTranslationProvider implements \TYPO3\FLOW3\I18n\TranslationProvider\TranslationProviderInterface {

	/**
	 * An absolute path to the directory where translation files reside.
	 *
	 * @var string
	 */
	protected $xliffBasePath = 'Private/Locale/Translations/';

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
	 */
	public function injectLocalizationService(\TYPO3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader
	 * @return void
	 */
	public function injectPluralsReader(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader $pluralsReader) {
		$this->pluralsReader = $pluralsReader;
	}

	/**
	 * Returns translated label of $originalLabel from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $originalLabel Label used as a key in order to find translation
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @param string $sourceName A relative path to the filename with translations (labels' catalog)
	 * @param string $packageKey Key of the package containing the source file
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTranslationByOriginalLabel($originalLabel, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.FLOW3') {
		$model = $this->getModel($packageKey, $sourceName, $locale);

		if ($pluralForm !== NULL) {
			$pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

			if (!is_array($pluralFormsForProvidedLocale) || !in_array($pluralForm, $pluralFormsForProvidedLocale)) {
				throw new \TYPO3\FLOW3\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
			}
				// We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
			$translation = $model->getTargetBySource($originalLabel, (int)array_search($pluralForm, $pluralFormsForProvidedLocale));
		} else {
			$translation = $model->getTargetBySource($originalLabel);
		}


		return $translation;
	}

	/**
	 * Returns label for a key ($labelId) from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $labelId Key used to find translated label
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @param string $sourceName A relative path to the filename with translations (labels' catalog)
	 * @param string $packageKey Key of the package containing the source file
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTranslationById($labelId, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.FLOW3') {
		$model = $this->getModel($packageKey, $sourceName, $locale);

		if ($pluralForm !== NULL) {
			$pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

			if (!in_array($pluralForm, $pluralFormsForProvidedLocale)) {
				throw new \TYPO3\FLOW3\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033387);
			}
			$translation = $model->getTargetByTransUnitId($labelId, (int)array_search($pluralForm, $pluralFormsForProvidedLocale));
		} else {
			$translation = $model->getTargetByTransUnitId($labelId);
		}

		return $translation;
	}

	/**
	 * Returns a XliffModel instance representing desired CLDR file.
	 *
	 * Will return existing instance if a model for given $sourceName was already
	 * requested before. Returns FALSE when $sourceName doesn't point to existing
	 * file.
	 *
	 * @param string $packageKey Key of the package containing the source file
	 * @param string $sourceName Relative path to existing CLDR file
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale object
	 * @return \TYPO3\FLOW3\I18n\Xliff\XliffModel New or existing instance
	 */
	protected function getModel($packageKey, $sourceName, \TYPO3\FLOW3\I18n\Locale $locale) {
		$sourceName = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('resource://' . $packageKey, $this->xliffBasePath, $sourceName . '.xlf'));
		$sourceName = $this->localizationService->getLocalizedFilename($sourceName, $locale);

		if (isset($this->models[$sourceName])) {
			return $this->models[$sourceName];
		}
		return $this->models[$sourceName] = new \TYPO3\FLOW3\I18n\Xliff\XliffModel($sourceName);
	}
}

?>