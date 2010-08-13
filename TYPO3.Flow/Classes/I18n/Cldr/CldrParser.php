<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Cldr;

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
 * A class which parses CLDR file to simple but useful array representation.
 *
 * Parsed data is an array where keys are nodes from XML file. If node
 * has any attributes, they will be placed without change as an element of
 * an array. Below are examples of parsed data structure.
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
 * Please note that there can be predefined index used anywhere on the end
 * of the tree (i.e., pointing to the leaf). It is a case when a node has
 * more than one element, from which one hasn't any attributes, and others
 * do have attributes. For example, such data:
 *
 * <dateFormat>
 *   <pattern>d MMM, yyyy G</pattern>
 *   <pattern alt="proposed-x1001" draft="unconfirmed">MMM d, yyyy G</pattern>
 * </dateFormat>
 *
 * will be converted to:
 * 'dateFormat' => array(
 *   'pattern' => array(
 *     NODE_WITHOUT_ATTRIBUTES => 'dd-MM-yyyy',
 *     'alt="proposed-x1001" draft="unconfirmed"' => 'd MMM y',
 *   )
 * )
 *
 * When node has only one element, and this element hasn't any attributes,
 * the predefined index won't be used (i.e. the element is placed directly
 * as a value of parent). If you remove second "pattern" child from the
 * example XML above, it will be parsed to such array:
 *
 * 'dateFormat' => array(
 *   'pattern' => 'dd-MM-yyyy',
 * )
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CldrParser extends \F3\FLOW3\I18n\Xml\AbstractXmlParser {

	/**
	 * A key for nodes without attributes
	 *
	 * Constant used as a key in parsed data array for nodes which don't have any
	 * attributes. Please see the documentation for this class for details.
	 *
	 * Note: cache will need to be flushed when this value is ever altered.
	 */
	const NODE_WITHOUT_ATTRIBUTES = '#noattributes';

	/**
	 * Parses the attributes' string and returns a value of attribute with
	 * desired name.
	 *
	 * Attributes are stored together with nodes in an array. If node has
	 * attributes, they are all stored as one string, in the same manner they
	 * exist in XML file (e.g. 'alt="proposed-x1001" draft="unconfirmed"').
	 *
	 * This convenient method extracts a value of desired attribute by its name
	 * (in example above, in order to get the value 'proposed-x1001', 'alt'
	 * should be passed as the second parameter to this method).
	 *
	 * Note: there isn't any validation for input variable.
	 *
	 * @param string $attributeString An attribute to parse
	 * @param int $desiredAtrributeName Name of the attribute to find
	 * @return mixed Value of desired attribute, or FALSE if there is no such attribute
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	static public function getValueOfAttributeByName($attributeString, $desiredAtrributeName) {
		$desiredAtrributeName .= '="';
		$positionOfAttributeName = strpos($attributeString, $desiredAtrributeName);

		if ($positionOfAttributeName === FALSE) {
			return FALSE;
		}

		$positionOfAttributeValue = $positionOfAttributeName + strlen($desiredAtrributeName);
		return substr($attributeString, $positionOfAttributeValue, strpos($attributeString, '"', $positionOfAttributeValue) - $positionOfAttributeValue);
	}

	/**
	 * Returns array representation of XML data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed CLDR File
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @see \F3\FLOW3\Xml\AbstractXmlParser::doParsingFromRoot()
	 */
	protected function doParsingFromRoot(\SimpleXMLElement $root) {
		return $this->parseNode($root);
	}

	/**
	 * Returns array representation of XML data, starting from a node pointed by
	 * $node variable.
	 *
	 * Please see the documentation of this class for details about the internal
	 * representation of XML data.
	 *
	 * @param \SimpleXMLElement $node A node to start parsing from
	 * @return mixed An array representing parsed XML node or string value if leaf
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
					$parsedNode[$nameOfChild] = array_merge(array(self::NODE_WITHOUT_ATTRIBUTES => $parsedNode[$nameOfChild]), $parsedChild);
				}
			} else {
				$parsedNode[$nameOfChild] = $parsedChild;
			}
		}

		return $parsedNode;
	}
}

?>