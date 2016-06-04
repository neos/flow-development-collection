<?php
namespace TYPO3\Fluid\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Base test class for testing view helpers
 */
abstract class ViewHelperBaseTestcase extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer
     */
    protected $viewHelperVariableContainer;

    /**
     * Mock contents of the $viewHelperVariableContainer in the format:
     * array(
     *  'Some\ViewHelper\Class' => array('key1' => 'value1', 'key2' => 'value2')
     * )
     *
     * @var array
     */
    protected $viewHelperVariableContainerData = array();

    /**
     * @var \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer
     */
    protected $templateVariableContainer;

    /**
     * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\Fluid\Core\ViewHelper\TagBuilder
     */
    protected $tagBuilder;

    /**
     * @var \TYPO3\Fluid\Core\ViewHelper\Arguments
     */
    protected $arguments;

    /**
     * @var \TYPO3\Flow\Mvc\ActionRequest
     */
    protected $request;

    /**
     * @var \TYPO3\Fluid\Core\Rendering\RenderingContext
     */
    protected $renderingContext;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->viewHelperVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer');
        $this->viewHelperVariableContainer->expects($this->any())->method('exists')->will($this->returnCallback(array($this, 'viewHelperVariableContainerExistsCallback')));
        $this->viewHelperVariableContainer->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'viewHelperVariableContainerGetCallback')));
        $this->templateVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer');
        $this->uriBuilder = $this->createMock('TYPO3\Flow\Mvc\Routing\UriBuilder');
        $this->uriBuilder->expects($this->any())->method('reset')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setArguments')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setSection')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setFormat')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setCreateAbsoluteUri')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setAddQueryString')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setArgumentsToBeExcludedFromQueryString')->will($this->returnValue($this->uriBuilder));
        // BACKPORTER TOKEN #1
        $httpRequest = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://localhost/foo'));
        $this->request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->setConstructorArgs(array($httpRequest))->getMock();
        $this->request->expects($this->any())->method('isMainRequest')->will($this->returnValue(true));
        $this->controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->tagBuilder = $this->createMock('TYPO3\Fluid\Core\ViewHelper\TagBuilder');
        $this->arguments = array();
        $this->renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();
        $this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);
        $this->renderingContext->injectViewHelperVariableContainer($this->viewHelperVariableContainer);
        $this->renderingContext->setControllerContext($this->controllerContext);
    }

    /**
     * @param string $viewHelperName
     * @param string $key
     * @return boolean
     */
    public function viewHelperVariableContainerExistsCallback($viewHelperName, $key)
    {
        return isset($this->viewHelperVariableContainerData[$viewHelperName][$key]);
    }

    /**
     * @param string $viewHelperName
     * @param string $key
     * @return boolean
     */
    public function viewHelperVariableContainerGetCallback($viewHelperName, $key)
    {
        return $this->viewHelperVariableContainerData[$viewHelperName][$key];
    }

    /**
     * @param AbstractViewHelper $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(AbstractViewHelper $viewHelper)
    {
        $viewHelper->setRenderingContext($this->renderingContext);
        $viewHelper->setArguments($this->arguments);
        if ($viewHelper instanceof AbstractTagBasedViewHelper) {
            $viewHelper->injectTagBuilder($this->tagBuilder);
        }
    }
}
