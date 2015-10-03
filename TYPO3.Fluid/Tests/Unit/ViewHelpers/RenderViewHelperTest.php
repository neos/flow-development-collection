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

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for RenderViewHelper
 */
class RenderViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\RenderViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->templateVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer();
        $this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);
        $this->viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\RenderViewHelper', array('dummy'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function loadSettingsIntoArgumentsSetsSettingsIfNoSettingsAreSpecified()
    {
        $arguments = array(
            'someArgument' => 'someValue'
        );
        $expected = array(
            'someArgument' => 'someValue',
            'settings' => 'theSettings'
        );
        $this->templateVariableContainer->add('settings', 'theSettings');

        $actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function loadSettingsIntoArgumentsDoesNotOverrideGivenSettings()
    {
        $arguments = array(
            'someArgument' => 'someValue',
            'settings' => 'specifiedSettings'
        );
        $expected = array(
            'someArgument' => 'someValue',
            'settings' => 'specifiedSettings'
        );
        $this->templateVariableContainer->add('settings', 'theSettings');

        $actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function loadSettingsIntoArgumentsDoesNotThrowExceptionIfSettingsAreNotInTemplateVariableContainer()
    {
        $arguments = array(
            'someArgument' => 'someValue'
        );
        $expected = array(
            'someArgument' => 'someValue'
        );

        $actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments);
        $this->assertEquals($expected, $actual);
    }
}
