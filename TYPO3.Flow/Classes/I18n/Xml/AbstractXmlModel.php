<?php
namespace TYPO3\FLOW3\I18n\Xml;

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
 * An abstract model representing data from one XML file.
 *
 * This is very generic class being base for all more concrete models for
 * any kind of XML data (XLIFF, CLDR).
 *
 * XML model uses XML parser (also being an abstract class) in order to parse
 * XML file to multi dimensional array. How it's done exacly depends on concrete
 * parser, which is set by concrete model, as $xmlParser property.
 *
 * Parsed data is cached under the key being absolute file path.
 *
 */
abstract class AbstractXmlModel {

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
	protected $xmlSourcePath = NULL;

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
	 * Injects the FLOW3_I18n_Xml_AbstractXmlModel cache
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
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
		}
	}

	/**
	 * Shutdowns the model. Parsed data is saved to the cache if needed.
	 *
	 * @return void
	 */
	public function shutdownObject() {
		if ($this->xmlSourcePath !== NULL) {
			$this->cache->set(md5($this->xmlSourcePath), $this->xmlParsedData);
		}
	}
}

?>