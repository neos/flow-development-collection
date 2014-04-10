<?php
namespace TYPO3\Flow\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An abstract class for all concrete classes that parses any kind of XML data.
 *
 * @Flow\Scope("singleton")
 */
abstract class AbstractXmlParser {

	/**
	 * Associative array of "filename => parsed data" pairs.
	 *
	 * @var array
	 */
	protected $parsedFiles;

	/**
	 * Returns parsed representation of XML file.
	 *
	 * Parses XML if it wasn't done before. Caches parsed data.
	 *
	 * @param string $sourcePath An absolute path to XML file
	 * @return array Parsed XML file
	 */
	public function getParsedData($sourcePath) {
		if (!isset($this->parsedFiles[$sourcePath])) {
			$this->parsedFiles[$sourcePath] = $this->parseXmlFile($sourcePath);
		}
		return $this->parsedFiles[$sourcePath];
	}

	/**
	 * Reads and parses XML file and returns internal representation of data.
	 *
	 * @param string $sourcePath An absolute path to XML file
	 * @return array Parsed XML file
	 * @throws \TYPO3\Flow\I18n\Exception\InvalidXmlFileException When SimpleXML couldn't load XML file
	 */
	protected function parseXmlFile($sourcePath) {
		if (!file_exists($sourcePath)) {
			throw new \TYPO3\Flow\I18n\Exception\InvalidXmlFileException('The path "' . $sourcePath . '" does not point to an existing and accessible XML file.', 1328879703);
		}
		libxml_use_internal_errors(TRUE);
		$rootXmlNode = simplexml_load_file($sourcePath, 'SimpleXmlElement', \LIBXML_NOWARNING);
		if ($rootXmlNode === FALSE) {
			$errors = array();
			foreach (libxml_get_errors() as $error) {
				$errorMessage = trim($error->message) . ' (line ' . $error->line . ', column ' . $error->column;
				if ($error->file) {
					$errorMessage .= ' in ' . $error->file;
				}
				$errors[] = $errorMessage . ')';
			}
			throw new \TYPO3\Flow\I18n\Exception\InvalidXmlFileException('Parsing the XML file failed. These error were reported:' . PHP_EOL . implode(PHP_EOL, $errors), 1278155987);
		}

		return $this->doParsingFromRoot($rootXmlNode);
	}

	/**
	 * Returns array representation of XML data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed XML file (structure depends on concrete parser)
	 */
	abstract protected function doParsingFromRoot(\SimpleXMLElement $root);

}
