<?php

declare(strict_types=1);

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
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Base test class for testing view helpers
 */
abstract class ViewHelperBaseTestcase extends UnitTestCase
{
    /**
     * @var \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer|MockObject
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
    protected $viewHelperVariableContainerData = [];

    /**
     * @var \Neos\Flow\Mvc\Routing\UriBuilder|MockObject
     */
    protected $uriBuilder;

    /**
     * @var \Neos\Flow\Mvc\Controller\ControllerContext|MockObject
     */
    protected $controllerContext;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var \Neos\Flow\Mvc\ActionRequest|MockObject
     */
    protected $request;

    /**
     * @var \Neos\FluidAdaptor\Core\Rendering\RenderingContext|MockObject
     */
    protected $renderingContext;

    /**
     * @var ViewHelperInterface|MockObject
     */
    protected $viewHelper;

    /**
     * @var TagBuilder|MockObject
     */
    protected $tagBuilder;

    /**
     * @var TemplateVariableContainer|MockObject
     */
    protected $templateVariableContainer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->templateVariableContainer = $this->createMock(TemplateVariableContainer::class);
        $this->viewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $this->viewHelperVariableContainer->method('exists')->willReturnCallback([$this, 'viewHelperVariableContainerExistsCallback']);
        $this->viewHelperVariableContainer->method('get')->willReturnCallback([$this, 'viewHelperVariableContainerGetCallback']);
        $this->viewHelperVariableContainer->method('addOrUpdate')->willReturnCallback([$this, 'viewHelperVariableContainerAddOrUpdateCallback']);
        $this->uriBuilder = $this->createMock(UriBuilder::class);
        $this->uriBuilder->method('reset')->willReturn(($this->uriBuilder));
        $this->uriBuilder->method('setArguments')->willReturn(($this->uriBuilder));
        $this->uriBuilder->method('setSection')->willReturn(($this->uriBuilder));
        $this->uriBuilder->method('setFormat')->willReturn(($this->uriBuilder));
        $this->uriBuilder->method('setCreateAbsoluteUri')->willReturn(($this->uriBuilder));
        $this->uriBuilder->method('setAddQueryString')->willReturn(($this->uriBuilder));
        $this->uriBuilder->method('setArgumentsToBeExcludedFromQueryString')->willReturn(($this->uriBuilder));

        $httpRequestFactory = new ServerRequestFactory(new UriFactory());
        $httpRequest = $httpRequestFactory->createServerRequest('GET', new Uri('http://localhost/foo'));

        $this->request = $this->createMock(ActionRequest::class);
        $this->request->method('isMainRequest')->willReturn((true));
        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getUriBuilder')->willReturn(($this->uriBuilder));
        $this->controllerContext->method('getRequest')->willReturn(($this->request));
        $this->arguments = [];
        $this->renderingContext = new RenderingContext([]);
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
     * @param string $viewHelperName
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function viewHelperVariableContainerAddOrUpdateCallback($viewHelperName, $key, $value)
    {
        $this->viewHelperVariableContainerData[$viewHelperName][$key] = $value;
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

    /**
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper
     * @param array $testArguments
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
     */
    protected function prepareArguments(\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper, array $testArguments = [])
    {
        $evaluatedArguments = [];
        $argumentDefinitions = $viewHelper->prepareArguments();
        foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
            if (isset($testArguments[$argumentName])) {
                $argumentValue = $testArguments[$argumentName];
                $evaluatedArguments[$argumentName] = $argumentValue;
            } else {
                $evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
            }
        }

        $viewHelper->setArguments($evaluatedArguments);
        $viewHelper->validateArguments();
        $viewHelper->initialize();
        return $viewHelper;
    }

    protected function simulateViewHelperChildNodeContent(AbstractViewHelper&MockObject $viewHelper, string|int $content): void
    {
        $viewHelper->method('renderChildren')->willReturn($content);
        $mockThenViewHelperNode = $this->createMock(ViewHelperNode::class);
        $mockThenViewHelperNode->method('evaluateChildNodes')->willReturn($content);
        $viewHelper->setViewHelperNode($mockThenViewHelperNode);
    }
}
