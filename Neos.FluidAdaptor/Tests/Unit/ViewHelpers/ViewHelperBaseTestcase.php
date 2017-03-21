<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Base test class for testing view helpers
 */
abstract class ViewHelperBaseTestcase extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer|\PHPUnit_Framework_MockObject_MockObject
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
     * @var \Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $templateVariableContainer;

    /**
     * @var \Neos\Flow\Mvc\Routing\UriBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uriBuilder;

    /**
     * @var \Neos\Flow\Mvc\Controller\ControllerContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $controllerContext;

    /**
     * @var TagBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tagBuilder;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var \Neos\Flow\Mvc\ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Neos\FluidAdaptor\Core\Rendering\RenderingContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderingContext;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->viewHelperVariableContainer = $this->createMock(\TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class);
        $this->viewHelperVariableContainer->expects($this->any())->method('exists')->will($this->returnCallback(array($this, 'viewHelperVariableContainerExistsCallback')));
        $this->viewHelperVariableContainer->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'viewHelperVariableContainerGetCallback')));
        $this->templateVariableContainer = $this->createMock(TemplateVariableContainer::class);
        $this->uriBuilder = $this->createMock(\Neos\Flow\Mvc\Routing\UriBuilder::class);
        $this->uriBuilder->expects($this->any())->method('reset')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setArguments')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setSection')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setFormat')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setCreateAbsoluteUri')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setAddQueryString')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setArgumentsToBeExcludedFromQueryString')->will($this->returnValue($this->uriBuilder));
        // BACKPORTER TOKEN #1
        $httpRequest = \Neos\Flow\Http\Request::create(new \Neos\Flow\Http\Uri('http://localhost/foo'));
        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->setConstructorArgs(array($httpRequest))->getMock();
        $this->request->expects($this->any())->method('isMainRequest')->will($this->returnValue(true));
        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->arguments = array();
        $this->renderingContext = new \Neos\FluidAdaptor\Core\Rendering\RenderingContext(new StandaloneView(), []);
        $this->renderingContext->setVariableProvider($this->templateVariableContainer);
        $this->renderingContext->setViewHelperVariableContainer($this->viewHelperVariableContainer);
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
