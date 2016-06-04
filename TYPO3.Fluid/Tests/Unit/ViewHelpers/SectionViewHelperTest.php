<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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

        $viewHelperNodeMock = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode')->disableOriginalConstructor()->getMock();
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
