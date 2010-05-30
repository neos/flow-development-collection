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
 * A model representing data from one CLDR file.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class CLDRModel implements \F3\FLOW3\Locale\CLDR\CLDRModelInterface {

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
	protected $rootXMLNode = NULL;

	/**
	 * Stores any data from cache or data that was read during this request.
	 *
	 * This variable is an array where keys are XPath strings and values are
	 * results fetched by corresponding XPaths. Results are also arrays (can be
	 * multi dimensional).
	 *
	 * Data duplication is present here, but this variable is cached, so most
	 * of the time there is no need to even parse XML (for commonly used queries).
	 * I.e. data is fetched in "lazy" manner, only when it's necessary.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Injects the FLOW3_Locale_CDLR_CLDRModel cache
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
	 * XPath queries are supported. Returns cached data if available.
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array of matching data, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function get($path) {
		if (isset($this->data[$path])) {
			return $this->data[$path];
		}

		if ($this->rootXMLNode === NULL) {
			$this->loadXMLFile();
		}

		$nodes = $this->rootXMLNode->xpath($path);

		if ($nodes === FALSE || empty($nodes)) {
			return FALSE;
		}

		$parsedNodes = array();
		foreach ($nodes as $node) {
			$parsedNodes = array_merge($parsedNodes, $this->parseNode($node));
		}

		$this->data[$path] = $parsedNodes;

		return $parsedNodes;
	}

	/**
	 * Returns array representation of XML data, starting from a node pointed by
	 * $node variable.
	 *
	 * Each node has "name", "attributes" and "content" associative keys. The
	 * content can contain a value (string), or an array with children. Please
	 * consider the following example:
	 *
	 * array(
     *   0 => array(
     *     'name' => 'foo',
	 *     'attributes' => array(
	 *       'key' => 'value',
	 *     ),
	 *     'content' => array(
	 *       0 => array (
	 *         // ... children, as above
	 *       ),
	 *     ),
     *   ),
     * );
	 *
	 * @param \SimpleXMLElement $node A node to start parsing from
	 * @return array Parsed XML (see this method's description)
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function parseNode(\SimpleXMLElement $node) {
			// The 0 index is for purpose here, it separates nodes in the resulting array
		$parsedNodes[0] = array(
			'name' => $node->getName(),
			'attributes' => array(),
		);

			// SimpleXMLElement instance is returned from attributes(), but we need an array
		foreach ($node->attributes() as $attributeKey => $attributeValue) {
			$parsedNodes[0]['attributes'][$attributeKey] = (string)$attributeValue;
		}

		if ($node->count() > 0) {
			$parsedChildren = array();

			foreach ($node->children() as $child) {
				$parsedChildren = array_merge($parsedChildren, $this->parseNode($child));
			}

			$parsedNodes[0]['content'] = $parsedChildren;
		} else {
			$parsedNodes[0]['content'] = (string)$node;
		}

		return $parsedNodes;
	}

	/**
	 * Loads and parses corresponding XML file.
	 *
	 * Note: The SimpleXML extension needs to load whole XML file to the memory.
	 *
	 * @return void
	 * @throws F3\FLOW3\Locale\Exception\InvalidCLDRDataException if $filename doesn't point to the existing file or XML is not well-formed
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function loadXMLFile() {
		$this->rootXMLNode = simplexml_load_file($this->sourceFilename);

		if ($this->rootXMLNode === FALSE) {
			throw new \F3\FLOW3\Locale\Exception\InvalidCLDRDataException('The path provided does not point to existing or accessible file. Please check if CLDR data is available.', 1275143455);
		}
	}
}

?>