<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for SectionViewHelper
 *
 */
class SectionViewHelperTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function sectionIsAddedToParseVariableContainer()
    {
        $section = new \TYPO3\Fluid\ViewHelpers\SectionViewHelper();

        $viewHelperNodeMock = $this->getMock('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', array(), array(), '', false);
        $viewHelperArguments = array(
            'name' => new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('sectionName')
        );

        $variableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer();

        $section->postParseEvent($viewHelperNodeMock, $viewHelperArguments, $variableContainer);

        $this->assertTrue($variableContainer->exists('sections'), 'Sections array was not created, albeit it should.');
        $sections = $variableContainer->get('sections');
        $this->assertEquals($sections['sectionName'], $viewHelperNodeMock, 'ViewHelperNode for section was not stored.');
    }
}
