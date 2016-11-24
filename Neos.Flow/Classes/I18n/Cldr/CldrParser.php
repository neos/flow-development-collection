<?php
namespace Neos\Flow\I18n\Cldr;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\AbstractXmlParser;

/**
 * A class which parses CLDR file to simple but useful array representation.
 *
 * Parsed data is an array where keys are nodes from XML file with its attributes
 * (if any). Only distinguishing attributes are taken into account (see [1]).
 * Below are examples of parsed data structure.
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
 *       'calendar[@type="gregorian"]' => array(
 *         'months' => ''
 *       ),
 *       'calendar[@type="buddhist"]' => array(
 *         'months' => ''
 *       ),
 *     )
 *   )
 * )
 *
 * @Flow\Scope("singleton")
 * @see http://www.unicode.org/reports/tr35/#Inheritance_and_Validity [1]
 */
class CldrParser extends AbstractXmlParser
{
    /**
     * Returns array representation of XML data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing parsed CLDR File
     * @see AbstractXmlParser::doParsingFromRoot()
     */
    protected function doParsingFromRoot(\SimpleXMLElement $root)
    {
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
     */
    protected function parseNode(\SimpleXMLElement $node)
    {
        $parsedNode = [];

        if ($node->count() === 0) {
            return (string)$node;
        }

        foreach ($node->children() as $child) {
            $nameOfChild = $child->getName();

            $parsedChild = $this->parseNode($child);

            if (count($child->attributes()) > 0) {
                $parsedAttributes = '';
                foreach ($child->attributes() as $attributeName => $attributeValue) {
                    if ($this->isDistinguishingAttribute($attributeName)) {
                        $parsedAttributes .= '[@' . $attributeName . '="' . $attributeValue . '"]';
                    }
                }

                $nameOfChild .= $parsedAttributes;
            }

            if (!isset($parsedNode[$nameOfChild])) {
                // We accept only first child when they are non distinguishable (i.e. they differs only by non-distinguishing attributes)
                $parsedNode[$nameOfChild] = $parsedChild;
            }
        }

        return $parsedNode;
    }

    /**
     * Checks if given attribute belongs to the group of distinguishing attributes
     *
     * Distinguishing attributes in CLDR serves to distinguish multiple elements
     * at the same level (most notably 'type').
     *
     * @param string $attributeName
     * @return boolean
     */
    protected function isDistinguishingAttribute($attributeName)
    {
        // Taken from SupplementalMetadata and hardcoded for now
        $distinguishingAttributes = ['key', 'request', 'id', '_q', 'registry', 'alt', 'iso4217', 'iso3166', 'mzone', 'from', 'to', 'type'];

        // These are not defined as distinguishing in CLDR but we need to preserve them for alias resolving later
        $distinguishingAttributes[] = 'source';
        $distinguishingAttributes[] = 'path';

        // These are needed for proper plurals handling
        $distinguishingAttributes[] = 'locales';
        $distinguishingAttributes[] = 'count';

        // we need this one for datetime parsing (default[@choice] nodes)
        $distinguishingAttributes[] = 'choice';

        return in_array($attributeName, $distinguishingAttributes);
    }
}
