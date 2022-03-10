<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Uri;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\Exception;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for the action uri view helper
 *
 */
class ActionViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * var \Neos\FluidAdaptor\ViewHelpers\Uri\ActionViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Uri\ActionViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderReturnsUriReturnedFromUriBuilder()
    {
        $this->uriBuilder->expects(self::any())->method('uriFor')->will(self::returnValue('some/uri'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'index']);
        $actualResult = $this->viewHelper->render();

        self::assertEquals('some/uri', $actualResult);
    }

    /**
     * @test
     */
    public function renderCorrectlyPassesDefaultArgumentsToUriBuilder()
    {
        $this->uriBuilder->expects(self::once())->method('withSection')->with('');
        $this->uriBuilder->expects(self::once())->method('withCreateAbsoluteUri')->with(false);
        $this->uriBuilder->expects(self::once())->method('withArguments')->with([]);
        $this->uriBuilder->expects(self::once())->method('withAddQueryString')->with(false);
        $this->uriBuilder->expects(self::once())->method('withArgumentsToBeExcludedFromQueryString')->with([]);
        $this->uriBuilder->expects(self::once())->method('withFormat')->with('');
        $this->uriBuilder->expects(self::once())->method('uriFor')->with('theActionName', [], null, null, null);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'theActionName']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlyPassesAllArgumentsToUriBuilder()
    {
        $this->uriBuilder->expects(self::once())->method('withSection')->with('someSection');
        $this->uriBuilder->expects(self::once())->method('withCreateAbsoluteUri')->with(true);
        $this->uriBuilder->expects(self::once())->method('withArguments')->with(['additional' => 'RouteParameters']);
        $this->uriBuilder->expects(self::once())->method('withAddQueryString')->with(true);
        $this->uriBuilder->expects(self::once())->method('withArgumentsToBeExcludedFromQueryString')->with(['arguments' => 'toBeExcluded']);
        $this->uriBuilder->expects(self::once())->method('withFormat')->with('someFormat');
        $this->uriBuilder->expects(self::once())->method('uriFor')->with('someAction', ['some' => 'argument'], 'someController', 'somePackage', 'someSubpackage');

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction', 'arguments' => ['some' => 'argument'], 'controller' => 'someController', 'package' => 'somePackage', 'subpackage' => 'someSubpackage', 'section' => 'someSection', 'format' => 'someFormat', 'additionalParams' => ['additional' => 'RouteParameters'], 'absolute' => true, 'addQueryString' => true, 'argumentsToBeExcludedFromQueryString' => ['arguments' => 'toBeExcluded']]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderThrowsViewHelperExceptionIfUriBuilderThrowsFlowException()
    {
        $this->uriBuilder->expects(self::any())->method('uriFor')->will(self::throwException(new \Neos\Flow\Exception('Mock Exception', 12345)));

        try {
            $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction']);
            $this->viewHelper->render();
        } catch (\Neos\FluidAdaptor\Core\ViewHelper\Exception $exception) {
        }
        self::assertEquals(12345, $exception->getPrevious()->getCode());
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfUseParentRequestIsSetAndTheCurrentRequestHasNoParentRequest()
    {
        $this->expectException(Exception::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'absolute' => false, 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderUsesParentRequestIfUseParentRequestIsSet()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Uri\ActionViewHelper::class, ['renderChildren']);

        $parentRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::atLeastOnce())->method('isMainRequest')->will(self::returnValue(false));
        $this->request->expects(self::atLeastOnce())->method('getParentRequest')->will(self::returnValue($parentRequest));

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects(self::any())->method('getUriBuilder')->will(self::returnValue($this->uriBuilder));
        $this->controllerContext->expects(self::any())->method('getRequest')->will(self::returnValue($this->request));

        $this->uriBuilder->expects(self::atLeastOnce())->method('forRequest')->with($parentRequest);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'absolute' => false, 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => true]);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderUsesParentRequestIfUseMainRequestIsSet()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Uri\ActionViewHelper::class, ['renderChildren']);

        $mainRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::atLeastOnce())->method('isMainRequest')->will(self::returnValue(false));
        $this->request->expects(self::atLeastOnce())->method('getMainRequest')->will(self::returnValue($mainRequest));

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects(self::any())->method('getUriBuilder')->will(self::returnValue($this->uriBuilder));
        $this->controllerContext->expects(self::any())->method('getRequest')->will(self::returnValue($this->request));

        $this->uriBuilder->expects(self::atLeastOnce())->method('forRequest')->with($mainRequest);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'absolute' => false, 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => false, 'useMainRequest' => true]);
        $viewHelper->render();
    }
}
