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
 * An interface for providers of translation labels (messages).
 *
 * Concrete implementation may throw an UnsupportedTranslationMethodException
 * if particular method is not available / implemented.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Karol Gusak <firstname@lastname.eu>
 */
interface TranslationProviderInterface {

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
	public function getTranslationByOriginalLabel($sourceName, $originalLabel, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER);

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
	public function getTranslationById($sourceName, $labelId, \TYPO3\FLOW3\I18n\Locale $locale, $pluralForm = \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER);
}

?>