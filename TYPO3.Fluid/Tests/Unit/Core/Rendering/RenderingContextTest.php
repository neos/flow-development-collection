<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Rendering;

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
 * Testcase for ParsingState
 *
 */
class RenderingContextTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Parsing state
     * @var \TYPO3\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected $renderingContext;

    public function setUp()
    {
        $this->renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();
    }

    /**
     * @test
     */
    public function templateVariableContainerCanBeReadCorrectly()
    {
        $templateVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer');
        $this->renderingContext->injectTemplateVariableContainer($templateVariableContainer);
        $this->assertSame($this->renderingContext->getTemplateVariableContainer(), $templateVariableContainer, 'Template Variable Container could not be read out again.');
    }

    /**
     * @test
     */
    public function controllerContextCanBeReadCorrectly()
    {
        $controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $this->renderingContext->setControllerContext($controllerContext);
        $this->assertSame($this->renderingContext->getControllerContext(), $controllerContext);
    }

    /**
     * @test
     */
    public function viewHelperVariableContainerCanBeReadCorrectly()
    {
        $viewHelperVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer');
        $this->renderingContext->injectViewHelperVariableContainer($viewHelperVariableContainer);
        $this->assertSame($viewHelperVariableContainer, $this->renderingContext->getViewHelperVariableContainer());
    }
}
