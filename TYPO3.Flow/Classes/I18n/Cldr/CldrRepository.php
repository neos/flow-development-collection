<?php
namespace TYPO3\FLOW3\I18n\Cldr;

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
 * The CldrRepository class
 *
 * CldrRepository manages CldrModel instances across the framework, so there is
 * only one instance of CldrModel for every unique CLDR data file or file group.
 *
 * @scope singleton
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CldrRepository {

	/**
	 * An absolute path to the directory where CLDR resides. It is changed only
	 * in tests.
	 *
	 * @var string
	 */
	protected $cldrBasePath = 'resource://TYPO3.FLOW3/Private/Locale/CLDR/Sources/';

	/**
	 * @var \TYPO3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * An array of models requested at least once in current request.
	 *
	 * This is an associative array with pairs as follow:
	 * ['path']['locale'] => $model,
	 *
	 * where 'path' is a file or directory path and 'locale' is a Locale object.
	 * For models representing one CLDR file, the 'path' points to a file and
	 * 'locale' is not used. For models representing few CLDR files connected
	 * with hierarchical relation, 'path' points to a directory where files
	 * reside and 'locale' is used to define which files are included in the
	 * relation (e.g. for locale 'en_GB' files would be: root + en + en_GB).
	 *
	 * @var array<\TYPO3\FLOW3\I18n\Cldr\CldrModel>
	 */
	protected $models;

	/**
	 * @param \TYPO3\FLOW3\I18n\Service $localizationService
	 * @return void
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function injectLocalizationService(\TYPO3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * Returns an instance of CldrModel which represents CLDR file found under
	 * specified path.
	 *
	 * Will return existing instance if a model for given $filename was already
	 * requested before. Returns FALSE when $filename doesn't point to existing
	 * file.
	 *
	 * @param string $filename Relative (from CLDR root) path to existing CLDR file
	 * @return mixed A \TYPO3\FLOW3\I18n\Cldr\CldrModel instance or FALSE on failure
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function getModel($filename) {
		$filename = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, $filename . '.xml'));

		if (isset($this->models[$filename])) {
			return $this->models[$filename];
		}

		if (!is_file($filename)) {
			return FALSE;
		}

		return $this->models[$filename] = new \TYPO3\FLOW3\I18n\Cldr\CldrModel(array($filename));
	}

	/**
	 * Returns an instance of CldrModel which represents group of CLDR files
	 * which are related in hierarchy.
	 *
	 * This method finds a group of CLDR files within $directoryPath dir,
	 * for particular Locale. Returned model represents whole locale-chain.
	 *
	 * For example, for locale en_GB, returned model could represent 'en_GB',
	 * 'en', and 'root' CLDR files.
	 *
	 * Returns FALSE when $directoryPath doesn't point to existing directory.
	 *
	 * @param \TYPO3\FLOW3\I18n\Locale $locale A locale
	 * @param string $directoryPath Relative path to existing CLDR directory which contains one file per locale (see 'main' directory in CLDR for example)
	 * @return mixed A \TYPO3\FLOW3\I18n\Cldr\CldrModel instance or FALSE on failure
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function getModelForLocale(\TYPO3\FLOW3\I18n\Locale $locale, $directoryPath = 'main') {
		$directoryPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, $directoryPath));

		if (isset($this->models[$directoryPath][(string)$locale])) {
			return $this->models[$directoryPath][(string)$locale];
		}

		if (!is_dir($directoryPath)) {
			return FALSE;
		}

		$filesInHierarchy = $this->findLocaleChain($locale, $directoryPath);

		return $this->models[$directoryPath][(string)$locale] = new \TYPO3\FLOW3\I18n\Cldr\CldrModel($filesInHierarchy);
	}

	/**
	 * Returns absolute paths to CLDR files connected in hierarchy
	 *
	 * For given locale, many CLDR files have to be merged in order to get full
	 * set of CLDR data. For example, for 'en_GB' locale, files 'root', 'en',
	 * and 'en_GB' should be merged.
	 *
	 * @param \TYPO3\FLOW3\I18n\Locale $locale A locale
	 * @param string $directoryPath Relative path to existing CLDR directory which contains one file per locale (see 'main' directory in CLDR for example)
	 * @return array<string> Absoulte paths to CLDR files in hierarchy
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	protected function findLocaleChain(\TYPO3\FLOW3\I18n\Locale $locale, $directoryPath) {
		$filesInHierarchy = array();
		$filesInHierarchy[] = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($directoryPath, (string)$locale . '.xml'));
		while (($parentLocale = $this->localizationService->getParentLocaleOf($locale)) !== NULL) {
			$filesInHierarchy[] = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($directoryPath, (string)$parentLocale . '.xml'));
		}
		$filesInHierarchy[] = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($directoryPath, 'root.xml'));

		rsort($filesInHierarchy);
		return $filesInHierarchy;
	}
}

?>