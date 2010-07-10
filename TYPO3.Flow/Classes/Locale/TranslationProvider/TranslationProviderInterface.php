<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\TranslationProvider;

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
 * An interface for providers of translation labels (messages).
 *
 * Concrete implementation may throw an UnsupportedTranslationMethodException
 * if particular method is not available / implemented.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Karol Gusak <firstname@lastname.eu>
 */
interface TranslationProviderInterface {

	/**
	 * Returns translated label of $originalLabel from a file defined by $filename.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $filename A path to the filename with translations
	 * @param string $originalLabel Label used as a key in order to find translation
	 * @param \F3\FLOW3\Locale\Locale $locale Locale to use
	 * @param string $pluralForm One of: zero, one, two, few, many, other
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTranslationByOriginalLabel($filename, $originalLabel, \F3\FLOW3\Locale\Locale $locale, $pluralForm = 'other');

	/**
	 * Returns label for a key ($id) from a file defined by $filename.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $filename A path to the filename with translations
	 * @param string $id Key used to find translated label
	 * @param \F3\FLOW3\Locale\Locale $locale Locale to use
	 * @param string $pluralForm One of: zero, one, two, few, many, other
	 * @return mixed Translated label or FALSE on failureeu>
	 */
	public function getTranslationById($filename, $id, \F3\FLOW3\Locale\Locale $locale, $pluralForm = 'other');
}

?>