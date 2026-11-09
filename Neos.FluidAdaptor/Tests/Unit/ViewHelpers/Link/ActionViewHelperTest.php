<?php

declare(strict_types=1);

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 */
final class ActionViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * var \Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(ActionViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class, ['setTagName', 'addAttribute', 'setContent']);
        $mockTagBuilder->method('setTagName')->with('a');
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'someUri');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->uriBuilder->method('uriFor')->willReturn(('someUri'));

        $this->viewHelper->method('renderChildren')->willReturn(('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'index']);
        $this->viewHelper->render();
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function renderThrowsViewHelperExceptionIfUriBuilderThrowsFlowException()
    {
        $this->uriBuilder->method('uriFor')->willThrowException(new \Neos\Flow\Exception('Mock Exception', 12345));
        try {
            $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction']);
            $this->viewHelper->render();
        } catch (Exception $exception) {
        }
        self::assertEquals(12345, $exception->getPrevious()->getCode());
    }

    #[Test]
    public function renderThrowsExceptionIfUseParentRequestIsSetAndTheCurrentRequestHasNoParentRequest()
    {
        $this->expectException(Exception::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderUsesParentRequestIfUseParentRequestIsSet()
    {
        $viewHelper = $this->getAccessibleMock(ActionViewHelper::class, ['renderChildren']);

        $parentRequest = $this->createStub(ActionRequest::class);

        $this->request = $this->createMock(ActionRequest::class);
        $this->request->expects($this->atLeastOnce())->method('isMainRequest')->willReturn((false));
        $this->request->expects($this->atLeastOnce())->method('getParentRequest')->willReturn(($parentRequest));

        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getUriBuilder')->willReturn(($this->uriBuilder));
        $this->controllerContext->method('getRequest')->willReturn(($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setRequest')->with($parentRequest);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => true]);
        $viewHelper->render();
    }

    #[Test]
    public function renderCreatesAbsoluteUrisByDefault()
    {
        $viewHelper = $this->getAccessibleMock(ActionViewHelper::class, ['renderChildren']);

        $parentRequest = $this->createStub(ActionRequest::class);

        $this->request = $this->createMock(ActionRequest::class);

        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getUriBuilder')->willReturn(($this->uriBuilder));
        $this->controllerContext->method('getRequest')->willReturn(($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setCreateAbsoluteUri')->with(true);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction']);
        $viewHelper->render();
    }


    #[Test]
    public function renderCreatesRelativeUrisIfAbsoluteIsFalse()
    {
        /** @var $viewHelper \Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper */
        $viewHelper = $this->getAccessibleMock(ActionViewHelper::class, ['renderChildren']);

        $parentRequest = $this->createStub(ActionRequest::class);

        $this->request = $this->createMock(ActionRequest::class);

        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getUriBuilder')->willReturn(($this->uriBuilder));
        $this->controllerContext->method('getRequest')->willReturn(($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setCreateAbsoluteUri')->with(false);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => false, 'absolute' => false]);
        $viewHelper->render();
    }

    #[Test]
    public function renderUsesParentRequestIfUseMainRequestIsSet()
    {
        $viewHelper = $this->getAccessibleMock(ActionViewHelper::class, ['renderChildren']);

        $mainRequest = $this->createStub(ActionRequest::class);

        $this->request = $this->createMock(ActionRequest::class);
        $this->request->expects($this->atLeastOnce())->method('isMainRequest')->willReturn((false));
        $this->request->expects($this->atLeastOnce())->method('getMainRequest')->willReturn(($mainRequest));

        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getUriBuilder')->willReturn(($this->uriBuilder));
        $this->controllerContext->method('getRequest')->willReturn(($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setRequest')->with($mainRequest);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper = $this->prepareArguments($viewHelper, ['action' => 'someAction', 'arguments' => [], 'controller' => null, 'package' => null, 'subpackage' => null, 'section' => '', 'format' => '', 'additionalParams' => [], 'addQueryString' => false, 'argumentsToBeExcludedFromQueryString' => [], 'useParentRequest' => false, 'absolute' => false, 'useMainRequest' => true]);
        $viewHelper->render();
    }
}
