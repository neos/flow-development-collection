<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Link;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 */
class ActionViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * var \Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper::class, ['renderChildren', 'registerRenderMethodArguments']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->createMock(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class, ['setTagName', 'addAttribute', 'setContent']);
        $mockTagBuilder->expects($this->any())->method('setTagName')->with('a');
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'someUri');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->uriBuilder->expects($this->any())->method('uriFor')->will($this->returnValue('someUri'));

        $this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'index']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlyPassesDefaultArgumentsToUriBuilder()
    {
        $this->uriBuilder->expects($this->once())->method('setSection')->with('');
        $this->uriBuilder->expects($this->once())->method('setArguments')->with([]);
        $this->uriBuilder->expects($this->once())->method('setAddQueryString')->with(false);
        $this->uriBuilder->expects($this->once())->method('setArgumentsToBeExcludedFromQueryString')->with([]);
        $this->uriBuilder->expects($this->once())->method('setFormat')->with('');
        $this->uriBuilder->expects($this->once())->method('uriFor')->with('theActionName', [], null, null, null);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'theActionName']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlyPassesAllArgumentsToUriBuilder()
    {
        $this->uriBuilder->expects($this->once())->method('setSection')->with('someSection');
        $this->uriBuilder->expects($this->once())->method('setArguments')->with(['additional' => 'RouteParameters']);
        $this->uriBuilder->expects($this->once())->method('setAddQueryString')->with(true);
        $this->uriBuilder->expects($this->once())->method('setArgumentsToBeExcludedFromQueryString')->with(['arguments' => 'toBeExcluded']);
        $this->uriBuilder->expects($this->once())->method('setFormat')->with('someFormat');
        $this->uriBuilder->expects($this->once())->method('uriFor')->with('someAction', ['some' => 'argument'], 'someController', 'somePackage', 'someSubpackage');

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction', 'arguments' => ['some' => 'argument'], 'controller' => 'someController', 'package' => 'somePackage', 'subpackage' => 'someSubpackage', 'section' => 'someSection', 'format' => 'someFormat', 'additionalParams' => ['additional' => 'RouteParameters'], 'addQueryString' => true, 'argumentsToBeExcludedFromQueryString' => ['arguments' => 'toBeExcluded']]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderThrowsViewHelperExceptionIfUriBuilderThrowsFlowException()
    {
        $this->uriBuilder->expects($this->any())->method('uriFor')->will($this->throwException(new \Neos\Flow\Exception('Mock Exception', 12345)));
        try {
            $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction']);
            $this->viewHelper->render();
        } catch (\Neos\FluidAdaptor\Core\ViewHelper\Exception $exception) {
        }
        $this->assertEquals(12345, $exception->getPrevious()->getCode());
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfUseParentRequestIsSetAndTheCurrentRequestHasNoParentRequest()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderUsesParentRequestIfUseParentRequestIsSet()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper::class, ['renderChildren', 'registerRenderMethodArguments']);

        $parentRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->request->expects($this->atLeastOnce())->method('isMainRequest')->will($this->returnValue(false));
        $this->request->expects($this->atLeastOnce())->method('getParentRequest')->will($this->returnValue($parentRequest));

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setRequest')->with($parentRequest);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => true]);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCreatesAbsoluteUrisByDefault()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper::class, ['renderChildren', 'registerRenderMethodArguments']);

        $parentRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setCreateAbsoluteUri')->with(true);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction']);
        $viewHelper->render();
    }


    /**
     * @test
     */
    public function renderCreatesRelativeUrisIfAbsoluteIsFalse()
    {
        /** @var $viewHelper \Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper */
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper::class, ['renderChildren', 'registerRenderMethodArguments']);

        $parentRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setCreateAbsoluteUri')->with(false);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => false, 'absolute' => false]);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderUsesParentRequestIfUseMainRequestIsSet()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper::class, ['renderChildren', 'registerRenderMethodArguments']);

        $mainRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->request->expects($this->atLeastOnce())->method('isMainRequest')->will($this->returnValue(false));
        $this->request->expects($this->atLeastOnce())->method('getMainRequest')->will($this->returnValue($mainRequest));

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setRequest')->with($mainRequest);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => false, 'absolute' => false, 'useMainRequest' => true]);
        $viewHelper->render();
    }
}
