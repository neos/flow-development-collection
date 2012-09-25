<?php
namespace TYPO3\Flow\I18n\Cldr;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * A model representing data from one or few CLDR files.
 *
 * When more than one file path is provided to the constructor, data from
 * all files will be parsed and merged according to the inheritance rules defined
 * in CLDR specification. Aliases are also resolved correctly.
 *
 */
class CldrModel {

	/**
	 * An absolute path to the directory where CLDR resides. It is changed only
	 * in tests.
	 *
	 * @var string
	 */
	protected $cldrBasePath = 'resource://TYPO3.Flow/Private/I18n/CLDR/Sources/';

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * Key used to store / retrieve cached data
	 *
	 * @var string
	 */
	protected $cacheKey;

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\CldrParser
	 */
	protected $cldrParser;

	/**
	 * Absolute path or path to the files represented by this class' instance.
	 *
	 * @var array<string>
	 */
	protected $sourcePaths;

	/**
	 * @var array
	 */
	protected $parsedData;

	/**
	 * Contructs the model
	 *
	 * Accepts array of absolute paths to CLDR files. This array can have one
	 * element (if model represents one CLDR file) or many elements (if group
	 * of CLDR files is going to be represented by this model).
	 *
	 * @param array<string> $sourcePaths
	 */
	public function __construct(array $sourcePaths) {
		$this->sourcePaths = $sourcePaths;

		$this->cacheKey = md5(implode(';', $sourcePaths));
	}

	/**
	 * Injects the Flow_I18n_Cldr_CldrModelCache cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * @param \TYPO3\Flow\I18n\Cldr\CldrParser $parser
	 * @return void
	 */
	public function injectParser(\TYPO3\Flow\I18n\Cldr\CldrParser $parser) {
		$this->cldrParser = $parser;
	}

	/**
	 * When it's called, CLDR file is parsed or cache is loaded, if available.
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->cache->has($this->cacheKey)) {
			$this->parsedData = $this->cache->get($this->cacheKey);
		} else {
			$this->parsedData = $this->parseFiles($this->sourcePaths);
			$this->parsedData = $this->resolveAliases($this->parsedData, '');
			$this->cache->set($this->cacheKey, $this->parsedData);
		}
	}

	/**
	 * Returns multi-dimensional array representing desired node and it's children,
	 * or a string value if the path points to a leaf.
	 *
	 * Syntax for paths is very simple. It's a group of array indices joined
	 * with a slash. It tries to emulate XPath query syntax to some extent.
	 * Examples:
	 *
	 * plurals/pluralRules
	 * dates/calendars/calendar[@type="gregorian"]
	 *
	 * Please see the documentation for CldrParser for details about parsed data
	 * structure.
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array or string of matching data, or FALSE on failure
	 * @see \TYPO3\Flow\I18n\Cldr\CldrParser
	 */
	public function getRawData($path) {
		if ($path === '/') {
			return $this->parsedData;
		}

		$pathElements = explode('/', trim($path, '/'));
		$data = $this->parsedData;

		foreach ($pathElements as $key) {
			if (isset($data[$key])) {
				$data = $data[$key];
			} else {
				return FALSE;
			}
		}

		return $data;
	}

	/**
	 * Returns multi-dimensional array representing desired node and it's children.
	 *
	 * This method will return FALSE if the path points to a leaf (i.e. a string,
	 * not an array).
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array of matching data, or FALSE on failure
	 * @see \TYPO3\Flow\I18n\Cldr\CldrParser
	 * @see \TYPO3\Flow\I18n\Cldr\CldrModel::getRawData()
	 */
	public function getRawArray($path) {
		$data = $this->getRawData($path);

		if (!is_array($data)) {
			return FALSE;
		}

		return $data;
	}

	/**
	 * Returns string value from a path given.
	 *
	 * Path must point to leaf. Syntax for paths is same as for getRawData.
	 *
	 * @param string $path A path to the element to get
	 * @return mixed String with desired element, or FALSE on failure
	 */
	public function getElement($path) {
		$data = $this->getRawData($path);

		if (is_array($data)) {
			return FALSE;
		} else {
			return $data;
		}
	}

	/**
	 * Returns all nodes with given name found within given path
	 *
	 * @param string $path A path to search in
	 * @param string $nodeName A name of the nodes to return
	 * @return mixed String with desired element, or FALSE on failure
	 */
	public function findNodesWithinPath($path, $nodeName) {
		$data = $this->getRawArray($path);

		if ($data === FALSE) {
			return FALSE;
		}

		$filteredData = array();
		foreach ($data as $nodeString => $children) {
			if ($this->getNodeName($nodeString) === $nodeName) {
				$filteredData[$nodeString] = $children;
			}
		}

		return $filteredData;
	}

	/**
	 * Returns node name extracted from node string
	 *
	 * The internal representation of CLDR uses array keys like:
	 * 'calendar[@type="gregorian"]'
	 * This method helps to extract the node name from such keys.
	 *
	 * @param string $nodeString String with node name and optional attribute(s)
	 * @return string Name of the node
	 */
	static public function getNodeName($nodeString) {
		$positionOfFirstAttribute = strpos($nodeString, '[@');

		if ($positionOfFirstAttribute === FALSE) {
			return $nodeString;
		}

		return substr($nodeString, 0, $positionOfFirstAttribute);
	}

	/**
	 * Parses the node string and returns a value of attribute for name provided.
	 *
	 * An internal representation of CLDR data used by this class is a simple
	 * multi dimensional array where keys are nodes' names. If node has attributes,
	 * they are all stored as one string (e.g. 'calendar[@type="gregorian"]' or
	 * 'calendar[@type="gregorian"][@alt="proposed-x1001"').
	 *
	 * This convenient method extracts a value of desired attribute by its name
	 * (in example above, in order to get the value 'gregorian', 'type' should
	 * be passed as the second parameter to this method).
	 *
	 * Note: this method does not validate the input!
	 *
	 * @param string $nodeString A node key to parse
	 * @param string $attributeName Name of the attribute to find
	 * @return mixed Value of desired attribute, or FALSE if there is no such attribute
	 */
	static public function getAttributeValue($nodeString, $attributeName) {
		$attributeName = '[@' . $attributeName . '="';
		$positionOfAttributeName = strpos($nodeString, $attributeName);

		if ($positionOfAttributeName === FALSE) {
			return FALSE;
		}

		$positionOfAttributeValue = $positionOfAttributeName + strlen($attributeName);
		return substr($nodeString, $positionOfAttributeValue, strpos($nodeString, '"]', $positionOfAttributeValue) - $positionOfAttributeValue);
	}

	/**
	 * Parses given CLDR files using CldrParser and merges parsed data.
	 *
	 * Merging is done with inheritance in mind, as defined in CLDR specification.
	 *
	 * @param array<string> $sourcePaths Absolute paths to CLDR files (can be one file)
	 * @return array Parsed and merged data
	 */
	protected function parseFiles(array $sourcePaths) {
		$parsedFiles = array();

		foreach ($sourcePaths as $sourcePath) {
			$parsedFiles[] = $this->cldrParser->getParsedData($sourcePath);
		}

			// Merge all data starting with most generic file so we get proper inheritance
		$parsedData = $parsedFiles[0];

		for ($i = 1; $i < count($parsedFiles); ++$i) {
			$parsedData = $this->mergeTwoParsedFiles($parsedData, $parsedFiles[$i]);
		}

		return $parsedData;
	}

	/**
	 * Merges two sets of data from two separate CLDR files into one array.
	 *
	 * Merging is done with inheritance in mind, as defined in CLDR specification.
	 *
	 * @param mixed $firstParsedData Part of data from first file (either array or string)
	 * @param mixed $secondParsedData Part of data from second file (either array or string)
	 * @return array Data merged from two files
	 */
	protected function mergeTwoParsedFiles($firstParsedData, $secondParsedData) {
		$mergedData = $firstParsedData;

		if (is_array($secondParsedData)) {
			foreach ($secondParsedData as $nodeString => $children) {
				if (isset($firstParsedData[$nodeString])) {
					$mergedData[$nodeString] = $this->mergeTwoParsedFiles($firstParsedData[$nodeString], $children);
				} else {
					$mergedData[$nodeString] = $children;
				}
			}
		} else {
			$mergedData = $secondParsedData;
		}

		return $mergedData;
	}

	/**
	 * Resolves any 'alias' nodes in parsed CLDR data.
	 *
	 * CLDR uses 'alias' tag which denotes places where data should be copied
	 * from. This tag has 'source' attribute pointing (by relative XPath query)
	 * to the source node - it should be copied with all it's children.
	 *
	 * @param mixed $data Part of internal array to resolve aliases for (string if leaf, array otherwise)
	 * @param string $currentPath Path to currently analyzed part of data
	 * @return mixed Modified (or unchanged) $data
	 * @throws \TYPO3\Flow\I18n\Cldr\Exception\InvalidCldrDataException When found alias tag which has unexpected structure
	 */
	protected function resolveAliases($data, $currentPath) {
		if (!is_array($data)) {
			return $data;
		}

		foreach ($data as $nodeString => $nodeChildren) {
			if (self::getNodeName($nodeString) === 'alias') {
				if (self::getAttributeValue($nodeString, 'source') !== 'locale') {
						// Value of source attribute can be 'locale' or particular locale identifier, but we do not support the second mode, ignore it silently
					break;
				}

				$sourcePath = self::getAttributeValue($nodeString, 'path');

					// Change relative path to absolute one
				$sourcePath = str_replace('../', '', $sourcePath, $countOfJumpsToParentNode);
				$sourcePath = str_replace('\'', '"', $sourcePath);
				$currentPathNodeNames = explode('/', $currentPath);
				for ($i = 0; $i < $countOfJumpsToParentNode; ++$i) {
					unset($currentPathNodeNames[count($currentPathNodeNames) - 1]);
				}
				$sourcePath = implode('/', $currentPathNodeNames) . '/'. $sourcePath;

				unset($data[$nodeString]);
				$sourceData = $this->getRawData($sourcePath);
				if (is_array($sourceData)) {
					$data = array_merge($sourceData, $data);
				}
				break;
			} else {
				$data[$nodeString] = $this->resolveAliases($data[$nodeString], ($currentPath === '') ? $nodeString : ($currentPath . '/' . $nodeString));
			}
		}

		return $data;
	}
}

?>