<?php

declare(strict_types=1);

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;
use Neos\FluidAdaptor\View\StandaloneView;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the StandaloneView
 */
final class StandaloneViewTest extends UnitTestCase
{
    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    protected function setUp(): void
    {
        $this->standaloneView = $this->getAccessibleMock(StandaloneView::class, []);
        $mockControllerContext = $this->createMock(ControllerContext::class);
        $mockControllerContext->method('getRequest')->willReturn(($this->createMock(ActionRequest::class)));
        $this->inject($this->standaloneView, 'controllerContext', $mockControllerContext);
    }

    #[Test]
    public function getLayoutPathAndFilenameThrowsExceptionIfSpecifiedLayoutRootPathIsNoDirectory()
    {
        $this->expectException(InvalidTemplateResourceException::class);
        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts');
        \file_put_contents('vfs://MyLayouts/NotAFolder', 'foo');
        $this->standaloneView->setLayoutRootPath('vfs://MyLayouts/NotAFolder');
        $this->standaloneView->getTemplatePaths()->getLayoutSource();
    }

    #[Test]
    public function getLayoutPathAndFilenameThrowsExceptionIfLayoutFileIsADirectory()
    {
        $this->expectException(InvalidTemplateResourceException::class);
        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts/NotAFile');
        $this->standaloneView->setLayoutRootPath('vfs://MyLayouts');
        $this->standaloneView->getTemplatePaths()->getLayoutSource('NotAFile');
    }

    #[Test]
    public function getPartialPathAndFilenameThrowsExceptionIfSpecifiedPartialRootPathIsNoDirectory()
    {
        $this->expectException(InvalidTemplateResourceException::class);
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials');
        \file_put_contents('vfs://MyPartials/NotAFolder', 'foo');
        $this->standaloneView->setPartialRootPath('vfs://MyPartials/NotAFolder');
        $this->standaloneView->getTemplatePaths()->getPartialSource('SomePartial');
    }

    #[Test]
    public function getPartialPathAndFilenameThrowsExceptionIfPartialFileIsADirectory()
    {
        $this->expectException(InvalidTemplateResourceException::class);
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials/NotAFile');
        $this->standaloneView->setPartialRootPath('vfs://MyPartials');
        $this->standaloneView->getTemplatePaths()->getPartialSource('NotAFile');
    }
}
