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

include_once(__DIR__ . '/Fixtures/TemplateViewFixture.php');

use org\bovigo\vfs\vfsStreamWrapper;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\View\TemplateView;

/**
 * Testcase for the TemplateView
 */
class TemplateViewTest extends UnitTestCase
{
    /**
     * Helper to build mock controller context needed to test expandGenericPathPattern.
     *
     * @param string $packageKey
     * @param string $subPackageKey
     * @param string $controllerName
     * @param string $format
     * @return ControllerContext
     */
    protected function setupMockControllerContextForPathResolving($packageKey, $subPackageKey, $controllerName, $format)
    {
        $controllerObjectName = 'Neos\\' . $packageKey . '\\' . ($subPackageKey != $subPackageKey . '\\' ? : '') . 'Controller\\' . $controllerName . 'Controller';

        $httpRequest = Request::create(new Uri('http://robertlemke.com/blog'));
        $mockRequest = $this->createMock(\Neos\Flow\Mvc\ActionRequest::class, array(), array($httpRequest));
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($packageKey));
        $mockRequest->expects($this->any())->method('getControllerSubPackageKey')->will($this->returnValue($subPackageKey));
        $mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
        $mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));

        /** @var $mockControllerContext ControllerContext */
        $mockControllerContext = $this->createMock(\Neos\Flow\Mvc\Controller\ControllerContext::class, array('getRequest'), array(), '', false);
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        return $mockControllerContext;
    }

    /**
     * @test
     */
    public function getTemplateRootPathsReturnsUserSpecifiedTemplatePaths()
    {
        $templateView = new TemplateView();

        $templateRootPaths = array('/foo/bar/', 'baz/');
        $templateView->setOption('templateRootPaths', $templateRootPaths);

        $actual = $templateView->getTemplatePaths()->getTemplateRootPaths();
        $this->assertEquals($templateRootPaths, $actual, 'A set template root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPartialRootPathsReturnsUserSpecifiedPartialPath()
    {
        $templateView = new TemplateView();

        $partialRootPaths = array('/foo/bar/', 'baz/');
        $templateView->setOption('partialRootPaths', $partialRootPaths);

        $actual = $templateView->getTemplatePaths()->getPartialRootPaths();
        $this->assertEquals($partialRootPaths, $actual, 'A set partial root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getLayoutRootPathsReturnsUserSpecifiedPartialPaths()
    {
        $templateView = new TemplateView();

        $layoutRootPaths = array('/foo/bar/', 'baz/');
        $templateView->setOption('layoutRootPaths', $layoutRootPaths);

        $actual = $templateView->getTemplatePaths()->getLayoutRootPaths();
        $this->assertEquals($layoutRootPaths, $actual, 'A set layout root path was not returned correctly.');
    }
}
