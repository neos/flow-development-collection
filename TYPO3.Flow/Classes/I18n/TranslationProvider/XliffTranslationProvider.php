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
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class XliffTranslationProvider implements \F3\FLOW3\Locale\TranslationProvider\TranslationProviderInterface {

	/**
	 * An absolute path to the directory where translation files reside.
	 * It is changed only in tests.
	 *
	 * @var string
	 */
	protected $xliffBasePath = 'resource://FLOW3/Private/Locale/Translations/';

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Locale\Service
	 */
	protected $localizationService;

	/**
	 * @var \F3\FLOW3\Locale\Cldr\Reader\PluralsReader
	 */
	protected $pluralsReader;

	/**
	 * A collection of models requested at least once in current request.
	 *
	 * This is an associative array with pairs as follow:
	 * ['filename'] => $model,
	 *
	 * @var array<\F3\FLOW3\Locale\Xliff\XliffModel>
	 */
	protected $models;

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \F3\FLOW3\Locale\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\F3\FLOW3\Locale\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \F3\FLOW3\Locale\Cldr\Reader\PluralsReader $pluralsReader
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectPluralsReader(\F3\FLOW3\Locale\Cldr\Reader\PluralsReader $pluralsReader) {
		$this->pluralsReader = $pluralsReader;
	}

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
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getTranslationByOriginalLabel($filename, $originalLabel, \F3\FLOW3\Locale\Locale $locale, $pluralForm = 'other') {
		$pluralForms = $this->pluralsReader->getPluralForms($locale);

		if (!in_array($pluralForm, $pluralForms)) {
				// Would an exception be better here?
			return FALSE;
		}

		$model = $this->getModel($filename, $locale);
			// We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
		$translation = $model->getTargetBySource($originalLabel, (int)array_search($pluralForm, $pluralForms));

		return $translation;
	}

	/**
	 * Returns label for a key ($id) from a file defined by $filename.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $filename A path to the filename with translations
	 * @param string $id Key used to find translated label
	 * @param \F3\FLOW3\Locale\Locale $locale Locale to use
	 * @param string $pluralForm One of: zero, one, two, few, many, other
	 * @return mixed Translated label or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getTranslationById($filename, $id, \F3\FLOW3\Locale\Locale $locale, $pluralForm = 'other') {
		$pluralForms = $this->pluralsReader->getPluralForms($locale);

		if (!in_array($pluralForm, $pluralForms)) {
			return FALSE;
		}

		$model = $this->getModel($filename, $locale);
		$translation = $model->getTargetByTransUnitId($id, (int)array_search($pluralForm, $pluralForms));

		return $translation;
	}

	/**
	 * Returns a XliffModel instance representing desired CLDR file.
	 *
	 * Will return existing instance if a model for given $filename was already
	 * requested before. Returns FALSE when $filename doesn't point to existing
	 * file.
	 *
	 * @param string $filename Relative path to existing CLDR file
	 * @return F3\FLOW3\Locale\Xliff\XliffModel New or existing instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function getModel($filename, \F3\FLOW3\Locale\Locale $locale) {
		$filename = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->xliffBasePath, $filename . '.xlf'));
		$filename = $this->localizationService->getLocalizedFilename($filename, $locale);

		if (isset($this->models[$filename])) {
			return $this->models[$filename];
		}

		return $this->models[$filename] = $this->objectManager->create('F3\FLOW3\Locale\Xliff\XliffModel', $filename);
	}
}

?>