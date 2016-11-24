<?php
namespace Neos\FluidAdaptor\Tests\Unit\View;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use org\bovigo\vfs\vfsStreamWrapper;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\View\StandaloneView;

/**
 * Testcase for the StandaloneView
 */
class StandaloneViewTest extends UnitTestCase
{
    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * @var ControllerContext
     */
    protected $mockControllerContext;

    /**
     * @var ActionRequest
     */
    protected $mockRequest;

    public function setUp()
    {
        $this->standaloneView = $this->getAccessibleMock(\Neos\FluidAdaptor\View\StandaloneView::class, array('dummy'));

        $this->mockRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockControllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockRequest));
        $this->inject($this->standaloneView, 'controllerContext', $this->mockControllerContext);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfSpecifiedLayoutRootPathIsNoDirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts');
        \file_put_contents('vfs://MyLayouts/NotAFolder', 'foo');
        $this->standaloneView->setLayoutRootPath('vfs://MyLayouts/NotAFolder');
        $this->standaloneView->getTemplatePaths()->getLayoutSource();
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfLayoutFileIsADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts/NotAFile');
        $this->standaloneView->setLayoutRootPath('vfs://MyLayouts');
        $this->standaloneView->getTemplatePaths()->getLayoutSource('NotAFile');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfSpecifiedPartialRootPathIsNoDirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials');
        \file_put_contents('vfs://MyPartials/NotAFolder', 'foo');
        $this->standaloneView->setPartialRootPath('vfs://MyPartials/NotAFolder');
        $this->standaloneView->getTemplatePaths()->getPartialSource('SomePartial');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfPartialFileIsADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials/NotAFile');
        $this->standaloneView->setPartialRootPath('vfs://MyPartials');
        $this->standaloneView->getTemplatePaths()->getPartialSource('NotAFile');
    }
}
