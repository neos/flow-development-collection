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

/**
 * An interface for providers of translation labels (messages).
 *
 * Concrete implementation may throw an UnsupportedTranslationMethodException
 * if particular method is not available / implemented.
 *
 */
interface TranslationProviderInterface {

	/**
	 * Returns translated label of $originalLabel from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $originalLabel Label used as a key in order to find translation
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
	 * @param string $packageKey Key of the package containing the source file
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTranslationByOriginalLabel($originalLabel, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER, $sourceName = 'Main', $packageKey = 'TYPO3.FLOW3');

	/**
	 * Returns label for a key ($labelId) from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $labelId Key used to find translated label
	 * @param \TYPO3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
	 * @param string $packageKey Key of the package containing the source file
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTranslationById($labelId, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER, $sourceName = 'Main', $packageKey = 'TYPO3.FLOW3');
}

?>