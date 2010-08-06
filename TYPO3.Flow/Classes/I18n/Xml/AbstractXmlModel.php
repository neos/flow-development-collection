<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Xml;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
abstract class AbstractXmlModel {

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $xmlCache;

	/**
	 * Concrete XML parser which is set by more specific model extending this
	 * class.
	 *
	 * @var \F3\FLOW3\I18n\Xml\AbstractXmlParser
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
	 * Injects the FLOW3_I18n_Xml_AbstractXmlModel cache
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->xmlCache = $cache;
	}

	/**
	 * Sets the path to XML file and loads data.
	 *
	 * When it's called, XML file is parsed (using parser set in $xmlParser)
	 * or cache is loaded, if available.
	 *
	 * @param string $sourcePath Absolute path to the XML file
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function initializeObject($sourcePath) {
		$this->xmlSourcePath = $sourcePath;

		if ($this->xmlCache->has($this->xmlSourcePath)) {
			$this->xmlParsedData = $this->xmlCache->get($this->xmlSourcePath);
		} else {
			$this->xmlParsedData = $this->xmlParser->getParsedData($this->xmlSourcePath);
		}
	}

	/**
	 * Shutdowns the model. Parsed data is saved to the cache if needed.
	 *
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function shutdownObject() {
		if ($this->xmlSourcePath !== NULL) {
			$this->xmlCache->set($this->xmlSourcePath, $this->xmlParsedData);
		}
	}
}

?>