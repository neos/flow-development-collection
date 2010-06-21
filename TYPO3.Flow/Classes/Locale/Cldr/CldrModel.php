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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class CldrModel implements \F3\FLOW3\Locale\Cldr\CldrModelInterface {

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * Absolute path to the file which is represented by this class instance.
	 *
	 * @var string
	 */
	protected $sourceFilename;

	/**
	 * Reference to the SimpleXMLElement object representing root node of XML file.
	 *
	 * @var \SimpleXMLElement
	 */
	protected $rootXmlNode = NULL;

	/**
	 * Stores any data from cache or data that was read during this request.
	 *
	 * This variable is an array where keys are nodes from XML file. If node
	 * has any attributes, they will be placed without change as an element of
	 * an array. Example:
	 *
	 * such XML data:
	 * <dates>
	 *   <calendars>
	 *     <calendar type="gregorian">
	 *       <months />
	 *     </calendar>
	 *     <calendar type="buddhist">
	 *       <months />
	 *     </calendar>
	 *   </calendars>
	 * </dates>
	 *
	 * will be converted to such array:
	 * array(
	 *   'dates' => array(
	 *     'calendars' => array(
	 *       'calendar' => array(
	 *         'type="gregorian"' => array(
	 *           'months' => ''
	 *         ),
	 *         'type="buddhist"' => array(
	 *           'months' => ''
	 *         ),
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * Please note that there can be an empty-string index anywhere on the end
	 * of the tree (e.g., it points to the leaf). It is a case when a node has
	 * many elements, from which one hasn't any attributes, and others have
	 * attributes. The former element can be accesed using getOneElement()
	 * method of this class. Please take a look at this example:
	 *
	 * 'dateFormat' => array(
	 *   'pattern' => array(
	 *     '' => 'dd-MM-yyyy',
	 *     'alt="proposed-x1001" draft="unconfirmed"' => 'd MMM y',
	 *   )
	 * )
	 *
	 * When node has only one element, and this element hasn't any attributes,
	 * no empty-string index is used (i.e. the element is placed directly as a
	 * value of parent).
	 *
	 * Whole XML file is parsed at once, but this variable is cached, so most
	 * of the time there is no need to do it.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Injects the FLOW3_Locale_Cldr_CldrModel cache
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Constructs the model.
	 *
	 * An absolute path to the XML file is required. Also loads the cache if
	 * available.
	 *
	 * @param string $filename Absolute path to the file
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function initializeObject($filename) {
		$this->sourceFilename = $filename;

		if ($this->cache->has($filename)) {
			$this->data = $this->cache->get($filename);
		} else {
			$this->data = $this->parseXmlFile();
			$this->data = $this->resolveAliases($this->data, '');
		}
	}

	/**
	 * Shutdowns the model. Parsed data is saved to the cache.
	 *
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function shutdownObject() {
		$this->cache->set($this->sourceFilename, $this->data);
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
	 * Please see the documentation for $data property of this class for details
	 * about array structure.
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array of matching data, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getRawArray($path) {
		$arrayKeys = explode('/', trim($path, '/'));
		$data = $this->data;

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
	 * In CLDR, when there is a node with element without attributes, and with
	 * elements with attributes, it means that the latter elements are
	 * alternatives for the former one, so it is safe to return the first one
	 * and ignore the rest.
	 *
	 * Syntax for paths is same as for getRawArray.
	 *
	 * @param string $path A path to the element to get
	 * @return mixed String with desired element, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getOneElement($path) {
		$data = $this->getRawArray($path);

		if ($data === FALSE) {
			return FALSE;
		} elseif (is_array($data)) {
			if (isset($data[''])) {
				return $data[''];
			} else {
				return FALSE;
			}
		} else {
			return $data;
		}
	}

	/**
	 * Parses the attributes string and returns a value of desired attribute.
	 *
	 * Attributes are stored together with nodes in an array. If node has
	 * attributes, they are all stored as one string, in the same manner they
	 * exist in XML file (e.g. 'alt="proposed-x1001" draft="unconfirmed"').
	 *
	 * This convenient method extracts a value of desired attribute (in example
	 * above, 'proposed-x1001' would be first) and returns it.
	 *
	 * Note: there isn't any validation for input variable.
	 *
	 * @param string $attribute An attribute to parse
	 * @param int $attributeNumber Index of attribute to get value for, starting from 1
	 * @return mixed Value of desired attribute, or FALSE if there is no such attribute
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getValueOfAttribute($attribute, $attributeNumber) {
		$attributes = explode('" ', $attribute);

		if (count($attributes) < $attributeNumber) {
			return FALSE;
		} elseif (count($attributes) === $attributeNumber) {
			return substr($attributes[$attributeNumber - 1], strpos($attributes[$attributeNumber - 1], '"') + 1, -1);
		} else {
			return substr($attributes[$attributeNumber - 1], strpos($attributes[$attributeNumber - 1], '"') + 1);
		}
	}

	/**
	 * Reads and parses XML file and returns internal representation of data.
	 *
	 * @return array Parsed XML file
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @throws \F3\FLOW3\Locale\Exception\InvalidCldrDataException When SimpleXML couldn't load XML file
	 */
	protected function parseXmlFile() {
		$this->rootXmlNode = simplexml_load_file($this->sourceFilename);

		if ($this->rootXmlNode === FALSE) {
			throw new \F3\FLOW3\Locale\Exception\InvalidCldrDataException('The path provided does not point to existing or accessible file. Please check if CLDR data is available.', 1275143455);
		}

		return $this->parseNode($this->rootXmlNode);
	}

	/**
	 * Returns array representation of XML data, starting from a node pointed by
	 * $node variable.
	 *
	 * Please see the documentation for $data property of this class for details
	 * about the internal representation of XML data.
	 *
	 * @param \SimpleXMLElement $node A node to start parsing from
	 * @return array Parsed XML node
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function parseNode(\SimpleXMLElement $node) {
		$parsedNode = array();

		if ($node->count() === 0) {
			return (string)$node;
		}

		foreach ($node->children() as $child) {
			$nameOfChild = $child->getName();

			if (!isset($parsedNode[$nameOfChild])) {
				$parsedNode[$nameOfChild] = array();
			}

			$parsedChild = $this->parseNode($child);

			if (count($child->attributes()) > 0) {
				$parsedAttributes = '';
				foreach ($child->attributes() as $attributeName => $attributeValue) {
					$parsedAttributes .= $attributeName . '="' . $attributeValue . '" ';
				}
				$parsedAttributes = rtrim($parsedAttributes);
				$parsedChild = array($parsedAttributes => $parsedChild);
			}

			if (is_array($parsedChild)) {
				if (is_array($parsedNode[$child->getName()])) {
					$parsedNode[$nameOfChild] = array_merge($parsedNode[$nameOfChild], $parsedChild);
				} else {
					$parsedNode[$nameOfChild] = array_merge(array('' => $parsedNode[$nameOfChild]), $parsedChild);
				}
			} else {
				$parsedNode[$nameOfChild] = $parsedChild;
			}
		}

		return $parsedNode;
	}

	/**
	 * Resolves any 'alias' nodes in parsed CLDR data.
	 *
	 * CLDR uses 'alias' tag which denotes places where data should be copied
	 * from. This tag has 'source' attribute pointing (by relative XPath query)
	 * to the source node - it should be copied with all it's children.
	 *
	 * @param string $data Part of internal array to resolve aliases for
	 * @param string $currentPath Path to currently analyzed part of data
	 * @return array Modified (or unchanged) $data
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @throws \F3\FLOW3\Locale\Exception\InvalidCldrDataException When found alias tag which has unexpected structure
	 */
	protected function resolveAliases($data, $currentPath) {
		if (!is_array($data)) {
			return $data;
		}

		foreach ($data as $nodeName => $nodeChildren) {
			if ($nodeName === 'alias') {
				if(!is_array($nodeChildren)) {
						// Tag is alias but it has not children, something is very wrong
					throw new \F3\FLOW3\Locale\Exception\InvalidCldrDataException('Encountered problem with alias tag. Please check if CLDR data is not corrupted.', 1276421398);
				}

				$aliasAttributes = array_keys($nodeChildren);
				$aliasAttributes = $aliasAttributes[0];
				if ($this->getValueOfAttribute($aliasAttributes, 1) !== 'locale') {
						// Value of source attribute can be other than 'locale', but we do not support it, ignore it silently
					break;
				}

					// Convert XPath to simple path used by this class (note that it can generate errors when CLDR will start to use more sophisticated XPath queries in alias tags)
				$sourcePath = $this->getValueOfAttribute($aliasAttributes, 2);
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