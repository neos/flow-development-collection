<?php
namespace TYPO3\FLOW3\I18n\Xliff;

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
 * A model representing data from one XLIFF file.
 *
 * Please note that plural forms for particular translation unit are accessed
 * with integer index (and not string like 'zero', 'one', 'many' etc). This is
 * because they are indexed such way in XLIFF files in order to not break tools'
 * support.
 *
 * There are very few XLIFF editors, but they are nice Gettext's .po editors
 * available. Gettext supports plural forms, but it indexes them using integer
 * numbers. Leaving it this way in .xlf files, makes it possible to easily convert
 * them to .po (e.g. using xliff2po from Translation Toolkit), edit with Poedit,
 * and convert back to .xlf without any information loss (using po2xliff).
 *
 * @see http://docs.oasis-open.org/xliff/v1.2/xliff-profile-po/xliff-profile-po-1.2-cd02.html#s.detailed_mapping.tu
 */
class XliffModel {

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * Concrete XML parser which is set by more specific model extending this
	 * class.
	 *
	 * @var \TYPO3\FLOW3\I18n\Xml\AbstractXmlParser
	 */
	protected $xmlParser;

	/**
	 * Absolute path to the file which is represented by this class instance.
	 *
	 * @var string
	 */
	protected $xmlSourcePath;

	/**
	 * Parsed data (structure depends on concrete model).
	 *
	 * @var array
	 */
	protected $xmlParsedData;

	/**
	 * @param string $sourcePath
	 */
	public function __construct($sourcePath) {
		$this->xmlSourcePath = $sourcePath;
	}

	/**
	 * Injects the FLOW3_I18n_XmlModelCache cache
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\Xliff\XliffParser $parser
	 * @return void
	 */
	public function injectParser(\TYPO3\FLOW3\I18n\Xliff\XliffParser $parser) {
		$this->xmlParser = $parser;
	}

	/**
	 * When it's called, XML file is parsed (using parser set in $xmlParser)
	 * or cache is loaded, if available.
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->cache->has(md5($this->xmlSourcePath))) {
			$this->xmlParsedData = $this->cache->get(md5($this->xmlSourcePath));
		} else {
			$this->xmlParsedData = $this->xmlParser->getParsedData($this->xmlSourcePath);
			$this->cache->set(md5($this->xmlSourcePath), $this->xmlParsedData);
		}
	}

	/**
	 * Returns translated label ("target" tag in XLIFF) from source-target
	 * pair where "source" tag equals to $source parameter.
	 *
	 * @param string $source Label in original language ("source" tag in XLIFF)
	 * @param string $pluralFormIndex Index of plural form to use (starts with 0)
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTargetBySource($source, $pluralFormIndex = 0) {
		foreach ($this->xmlParsedData as $translationUnit) {
				// $source is always singular (or only) form, so compare with index 0
			if ($translationUnit[0]['source'] !== $source) {
				continue;
			}

			if (count($translationUnit) <= $pluralFormIndex) {
				return FALSE;
			}

			return $translationUnit[$pluralFormIndex]['target'];
		}

		return FALSE;
	}

	/**
	 * Returns translated label ("target" tag in XLIFF) for the id given.
	 *
	 * Id is compared with "id" attribute of "trans-unit" tag (see XLIFF
	 * specification for details).
	 *
	 * @param string $transUnitId The "id" attribute of "trans-unit" tag in XLIFF
	 * @param string $pluralFormIndex Index of plural form to use (starts with 0)
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTargetByTransUnitId($transUnitId, $pluralFormIndex = 0) {
		if (!isset($this->xmlParsedData[$transUnitId])) {
			return FALSE;
		}

		$translationUnit = $this->xmlParsedData[$transUnitId];
		if (count($translationUnit) <= $pluralFormIndex) {
			return FALSE;
		}

		return $translationUnit[$pluralFormIndex]['target'];
	}
}

?>