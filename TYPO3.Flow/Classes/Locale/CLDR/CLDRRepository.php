<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\CLDR;

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
 * The CLDRRepository class
 *
 * CLDRRepository manages CLDRModel and HierarchicalCLDRModel instances
 * across the framework, so there is only one instance of CLDRModel for
 * every unique CLDR data file, and one instace of HierarchicalCLDRModel
 * for every unique locale chain.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CLDRRepository {

	/**
	 * An absolute path to the directory where CLDR resides. It is changed only
	 * in tests.
	 *
	 * @var string
	 */
	protected $cldrBasePath = 'resource://FLOW3/Private/Locale/CLDR/Sources/';

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Locale\Service
	 */
	protected $localizationService;

	/**
	 * A collection of models requested at least once in current request.
	 *
	 * This is an associative array with pairs as follow:
	 * ['filename'] => $model,
	 * ['directoryPath']['localeTag'] => $hierarchicalModel,
	 *
	 * Hierarchical models describe a group of models. There can be many models
	 * for same directoryPaths, as there can be many locale chains.
	 *
	 * @var array<\F3\FLOW3\Locale\CLDR\CLDRModelInterface>
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
	 * Returns a CLDRModel instance representing desired CLDR file.
	 *
	 * Will return existing instance if a model for given $filename was already
	 * requested before. Returns FALSE when $filename doesn't point to existing
	 * file.
	 *
	 * @param string $filename Relative path to existing CLDR file
	 * @return mixed A F3\FLOW3\Locale\CLDR\CLDRModel instance or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getModel($filename) {
		$filename = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, $filename . '.xml'));

		if (isset($this->models[$filename])) {
			return $this->models[$filename];
		}

		if (!is_file($filename)) {
			return FALSE;
		}

		$this->models[$filename] = $this->objectManager->create('F3\FLOW3\Locale\CLDR\CLDRModel', $filename);
		return $this->models[$filename];
	}

	/**
	 * Returns a HierarchicalCLDRModel instance representing desired CLDR files.
	 *
	 * This method finds a group of CLDR files within $directoryPath dir,
	 * taking into account provided (or default) Locale. Returned model
	 * represents whole locale-chain.
	 *
	 * For example, for locale en_GB, returned model could represent en_GB, en,
	 * and root CLDR files.
	 *
	 * Returns FALSE when $directoryPath doesn't point to existing directory.
	 *
	 * @param string $directoryPath Relative path to existing CLDR directory which contains one file per locale (see 'main' directory in CLDR for example)
	 * @return mixed A F3\FLOW3\Locale\CLDR\HierarchicalCLDRModel instance or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getHierarchicalModel($directoryPath, \F3\FLOW3\Locale\Locale $locale = NULL) {
		$directoryPath = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, $directoryPath));

		if ($locale === NULL) {
			$locale = $this->localizationService->getDefaultLocale();
		}

		if (isset($this->models[$directoryPath][(string)$locale])) {
			return $this->models[$directoryPath][(string)$locale];
		}

		if (!is_dir($directoryPath)) {
			return FALSE;
		}

		$modelsInHierarchy = array();
		$modelsInHierarchy[] = $this->objectManager->create('F3\FLOW3\Locale\CLDR\CLDRModel', \F3\FLOW3\Utility\Files::concatenatePaths(array($directoryPath, (string)$locale . '.xml')));
		while (($parentLocale = $this->localizationService->getParentLocaleOf($locale)) !== NULL) {
			$modelsInHierarchy[] = $this->objectManager->create('F3\FLOW3\Locale\CLDR\CLDRModel', \F3\FLOW3\Utility\Files::concatenatePaths(array($directoryPath, (string)$parentLocale . '.xml')));
		}
		$modelsInHierarchy[] = $this->objectManager->create('F3\FLOW3\Locale\CLDR\CLDRModel', \F3\FLOW3\Utility\Files::concatenatePaths(array($directoryPath, 'root.xml')));

		$this->models[$directoryPath][(string)$locale] = $this->objectManager->create('F3\FLOW3\Locale\CLDR\HierarchicalCLDRModel', $modelsInHierarchy);
		return $this->models[$directoryPath][(string)$locale];
	}
}

?>