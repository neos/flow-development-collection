<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\Cldr;

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
 * A model representing data from one CLDR file.
 *
 * This class adds CLDR-specific functionality to more generic abstract XML Model.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class CldrModel extends \F3\FLOW3\Locale\Xml\AbstractXmlModel {

	/**
	 * An absolute path to the directory where CLDR resides. It is changed only
	 * in tests.
	 *
	 * @var string
	 */
	protected $cldrBasePath = 'resource://FLOW3/Private/Locale/CLDR/Sources/';

	/**
	 * @param \F3\FLOW3\Locale\Cldr\CldrParser $parser
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectParser(\F3\FLOW3\Locale\Cldr\CldrParser $parser) {
		$this->xmlParser = $parser;
	}

	/**
	 * Initializes object and loads the CLDR file
	 *
	 * @param string $sourcePath Absolute path to CLDR file
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function initializeObject($sourcePath) {
		if (!file_exists($sourcePath)) {
			$sourcePath = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, $sourcePath . '.xml'));
		}

		parent::initializeObject($sourcePath);

		if (!$this->xmlCache->has($this->xmlSourcePath)) {
				// Data was not loaded from cache (by parent), but was just parsed, so there wasn't alias resolving done before
			$this->xmlParsedData = $this->resolveAliases($this->xmlParsedData, '');
		}
	}

	/**
	 * Returns multi-dimensional array representing desired node and it's children.
	 *
	 * Syntax for paths is very simple. It's a group of array indices joined
	 * with a slash. Examples:
	 *
	 * plurals/pluralRules
	 * dates/calendars/calendar/type="gregorian"/
	 *
	 * Please see the documentation for \F3\FLOW3\Locale\Cldr\CldrParser for
	 * details about parsed data structure.
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array of matching data, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getRawArray($path) {
		$arrayKeys = explode('/', trim($path, '/'));
		$data = $this->xmlParsedData;

		foreach ($arrayKeys as $key) {
			if (isset($data[$key])) {
				$data = $data[$key];
			} else {
				return FALSE;
			}
		}

		return $data;
	}

	/**
	 * Returns string element from a path given.
	 *
	 * Path must point to leaf, or to node which has only one element (which is
	 * leaf), or to node which has element without attributes (which is leaf).
	 *
	 * Syntax for paths is same as for getRawArray.
	 *
	 * @param string $path A path to the element to get
	 * @return mixed String with desired element, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getElement($path) {
		$data = $this->getRawArray($path);

		if ($data === FALSE) {
			return FALSE;
		} elseif (is_array($data)) {
			if (isset($data[\F3\FLOW3\Locale\Cldr\CldrParser::NODE_WITHOUT_ATTRIBUTES])) {
				return $data[\F3\FLOW3\Locale\Cldr\CldrParser::NODE_WITHOUT_ATTRIBUTES];
			} else {
				return FALSE;
			}
		} else {
			return $data;
		}
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
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @throws \F3\FLOW3\Locale\Cldr\Exception\InvalidCldrDataException When found alias tag which has unexpected structure
	 */
	protected function resolveAliases($data, $currentPath) {
		if (!is_array($data)) {
			return $data;
		}

		foreach ($data as $nodeName => $nodeChildren) {
			if ($nodeName === 'alias') {
				if(!is_array($nodeChildren)) {
						// This is an alias tag but it has not any children, something is very wrong
					throw new \F3\FLOW3\Locale\Cldr\Exception\InvalidCldrDataException('Encountered problem with alias tag. Please check if CLDR data is not corrupted.', 1276421398);
				}

				$aliasAttributes = array_keys($nodeChildren);
				$aliasAttributes = $aliasAttributes[0];
				if (\F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttributeByName($aliasAttributes, 'source') !== 'locale') {
						// Value of source attribute can be other than 'locale', but we do not support it, ignore it silently
					break;
				}

					// Convert XPath to simple path used by this class (note that it can generate errors when CLDR will start to use more sophisticated XPath queries in alias tags)
				$sourcePath = \F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttributeByName($aliasAttributes, 'path');
				$sourcePath = str_replace(array('\'', '[@', ']'), array('"', '/', ''), $sourcePath);
				$sourcePath = str_replace('../', '', $sourcePath, $countOfJumpsToParentNode);

				$currentPathNodeNames = explode('/', $currentPath);
				for ($i = 0; $i < $countOfJumpsToParentNode; ++$i) {
					$indexOfLastNodeInPath = count($currentPathNodeNames) - 1;
					if (strpos($currentPathNodeNames[$indexOfLastNodeInPath], '"') !== FALSE) {
							// Attributes are not counted in path traverse
						--$i;
					}
					unset($currentPathNodeNames[$indexOfLastNodeInPath]);
				}

				$sourcePath = implode('/', $currentPathNodeNames) . '/'. $sourcePath;
				$data = $this->getRawArray($sourcePath);
				break;
			} else {
				$data[$nodeName] = $this->resolveAliases($data[$nodeName], ($currentPath === '') ? $nodeName : ($currentPath . '/' . $nodeName));
			}
		}

		return $data;
	}
}

?>