<?php
namespace Neos\FluidAdaptor\Service;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Reflection\ClassReflection;
use Neos\FluidAdaptor\Core\ViewHelper\ArgumentDefinition;

/**
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for auto-completion
 * in schema-aware editors like Eclipse XML editor.
 */
class XsdGenerator extends AbstractGenerator
{
    /**
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * Generate the XML Schema definition for a given namespace.
     * It will generate an XSD file for all view helpers in this namespace.
     *
     * @param string $viewHelperNamespace Namespace identifier to generate the XSD for, without leading Backslash.
     * @param string $xsdNamespace $xsdNamespace unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers")
     * @return string XML Schema definition
     * @throws Exception
     */
    public function generateXsd($viewHelperNamespace, $xsdNamespace)
    {
        if (substr($viewHelperNamespace, -1) !== '\\') {
            $viewHelperNamespace .= '\\';
        }

        $classNames = $this->getClassNamesInNamespace($viewHelperNamespace);
        if (count($classNames) === 0) {
            throw new Exception(sprintf('No ViewHelpers found in namespace "%s"', $viewHelperNamespace), 1330029328);
        }

        $xmlRootNode = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
			<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="' . $xsdNamespace . '"></xsd:schema>');

        foreach ($classNames as $className) {
            $this->generateXmlForClassName($className, $viewHelperNamespace, $xmlRootNode);
        }

        return $xmlRootNode->asXML();
    }

    /**
     * Generate the XML Schema for a given class name.
     *
     * @param string $className Class name to generate the schema for.
     * @param string $viewHelperNamespace Namespace prefix. Used to split off the first parts of the class name.
     * @param \SimpleXMLElement $xmlRootNode XML root node where the xsd:element is appended.
     * @return void
     */
    protected function generateXmlForClassName($className, $viewHelperNamespace, \SimpleXMLElement $xmlRootNode)
    {
        $reflectionClass = new ClassReflection($className);
        if (!$reflectionClass->isSubclassOf($this->abstractViewHelperReflectionClass)) {
            return;
        }

        $tagName = $this->getTagNameForClass($className, $viewHelperNamespace);

        $xsdElement = $xmlRootNode->addChild('xsd:element');
        $xsdElement['name'] = $tagName;
        $this->docCommentParser->parseDocComment($reflectionClass->getDocComment());
        $this->addDocumentation($this->docCommentParser->getDescription(), $xsdElement);

        $xsdComplexType = $xsdElement->addChild('xsd:complexType');
        $xsdComplexType['mixed'] = 'true';
        $xsdSequence = $xsdComplexType->addChild('xsd:sequence');
        $xsdAny = $xsdSequence->addChild('xsd:any');
        $xsdAny['minOccurs'] = '0';
        $xsdAny['maxOccurs'] = 'unbounded';

        $this->addAttributes($className, $xsdComplexType);
    }

    /**
     * Add attribute descriptions to a given tag.
     * Initializes the view helper and its arguments, and then reads out the list of arguments.
     *
     * @param string $className Class name where to add the attribute descriptions
     * @param \SimpleXMLElement $xsdElement XML element to add the attributes to.
     * @return void
     */
    protected function addAttributes($className, \SimpleXMLElement $xsdElement)
    {
        $viewHelper = $this->objectManager->get($className);
        $argumentDefinitions = $viewHelper->prepareArguments();

        /** @var $argumentDefinition ArgumentDefinition */
        foreach ($argumentDefinitions as $argumentDefinition) {
            $xsdAttribute = $xsdElement->addChild('xsd:attribute');
            $xsdAttribute['type'] = 'xsd:string';
            $xsdAttribute['name'] = $argumentDefinition->getName();
            $this->addDocumentation($argumentDefinition->getDescription(), $xsdAttribute);
            if ($argumentDefinition->isRequired()) {
                $xsdAttribute['use'] = 'required';
            }
        }
    }

    /**
     * Add documentation XSD to a given XML node
     *
     * @param string $documentation Documentation string to add.
     * @param \SimpleXMLElement $xsdParentNode Node to add the documentation to
     * @return void
     */
    protected function addDocumentation($documentation, \SimpleXMLElement $xsdParentNode)
    {
        $xsdAnnotation = $xsdParentNode->addChild('xsd:annotation');
        $this->addChildWithCData($xsdAnnotation, 'xsd:documentation', $documentation);
    }
}
