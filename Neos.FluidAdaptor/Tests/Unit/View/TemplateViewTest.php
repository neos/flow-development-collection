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

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
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

        $httpRequest = new ServerRequest('GET', new Uri('http://robertlemke.com/blog'));
        $mockRequest = $this->createMock(\Neos\Flow\Mvc\ActionRequest::class, [], [$httpRequest]);
        $mockRequest->expects(self::any())->method('getControllerPackageKey')->will(self::returnValue($packageKey));
        $mockRequest->expects(self::any())->method('getControllerSubPackageKey')->will(self::returnValue($subPackageKey));
        $mockRequest->expects(self::any())->method('getControllerName')->will(self::returnValue($controllerName));
        $mockRequest->expects(self::any())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $mockRequest->expects(self::any())->method('getFormat')->will(self::returnValue($format));

        /** @var $mockControllerContext ControllerContext */
        $mockControllerContext = $this->createMock(\Neos\Flow\Mvc\Controller\ControllerContext::class, ['getRequest'], [], '', false);
        $mockControllerContext->expects(self::any())->method('getRequest')->will(self::returnValue($mockRequest));

        return $mockControllerContext;
    }

    /**
     * @test
     */
    public function getTemplateRootPathsReturnsUserSpecifiedTemplatePaths()
    {
        $templateView = new TemplateView();

        $templateRootPaths = ['/foo/bar/', 'baz/'];
        $templateView->setOption('templateRootPaths', $templateRootPaths);

        $actual = $templateView->getTemplatePaths()->getTemplateRootPaths();
        self::assertEquals($templateRootPaths, $actual, 'A set template root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPartialRootPathsReturnsUserSpecifiedPartialPath()
    {
        $templateView = new TemplateView();

        $partialRootPaths = ['/foo/bar/', 'baz/'];
        $templateView->setOption('partialRootPaths', $partialRootPaths);

        $actual = $templateView->getTemplatePaths()->getPartialRootPaths();
        self::assertEquals($partialRootPaths, $actual, 'A set partial root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getLayoutRootPathsReturnsUserSpecifiedPartialPaths()
    {
        $templateView = new TemplateView();

        $layoutRootPaths = ['/foo/bar/', 'baz/'];
        $templateView->setOption('layoutRootPaths', $layoutRootPaths);

        $actual = $templateView->getTemplatePaths()->getLayoutRootPaths();
        self::assertEquals($layoutRootPaths, $actual, 'A set layout root path was not returned correctly.');
    }
}
