<?php
namespace TYPO3\Fluid\Tests\Unit\View;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

include_once(__DIR__ . '/Fixtures/TransparentSyntaxTreeNode.php');
include_once(__DIR__ . '/Fixtures/TemplateViewFixture.php');

use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\View\TemplateView;

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
        $controllerObjectName = 'TYPO3\\' . $packageKey . '\\' . ($subPackageKey != $subPackageKey . '\\' ? : '') . 'Controller\\' . $controllerName . 'Controller';

        $httpRequest = Request::create(new Uri('http://robertlemke.com/blog'));
        $mockRequest = $this->createMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array($httpRequest));
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($packageKey));
        $mockRequest->expects($this->any())->method('getControllerSubPackageKey')->will($this->returnValue($subPackageKey));
        $mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
        $mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));

        /** @var $mockControllerContext ControllerContext */
        $mockControllerContext = $this->createMock(\TYPO3\Flow\Mvc\Controller\ControllerContext::class, array('getRequest'), array(), '', false);
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        return $mockControllerContext;
    }


    public function expandGenericPathPatternDataProvider()
    {
        return array(
            // bubbling controller & subpackage parts and optional format
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/Package/@action',
                    'Resources/Private/Templates/@action.html',
                    'Resources/Private/Templates/@action',
                )
            ),
            // just optional format
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates/',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                )
            ),
            // just bubbling controller & subpackage parts
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => '@partialRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Resources/Private/Partials/Some/Sub/Package/SomeController/@action.json',
                    'Resources/Private/Partials/Some/Sub/Package/@action.json',
                    'Resources/Private/Partials/Sub/Package/@action.json',
                    'Resources/Private/Partials/Package/@action.json',
                    'Resources/Private/Partials/@action.json',
                )
            ),
            // layoutRootPath
            array(
                'package' => 'Some.Package',
                'subPackage' => null,
                'controller' => null,
                'format' => 'xml',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Resources/Private/Layouts/@action.xml',
                    'Resources/Private/Layouts/@action',
                )
            ),
            // partialRootPath
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => null,
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/Package/@action',
                    'Resources/Private/Templates/@action.html',
                    'Resources/Private/Templates/@action',
                )
            ),
            // optional format as directory name
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'xml',
                'templateRootPath' => 'Resources/Private/Templates_@format',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action',
                'expectedResult' => array(
                    'Resources/Private/Templates_xml/Some/Sub/Package/SomeController/@action',
                    'Resources/Private/Templates_/Some/Sub/Package/SomeController/@action',
                )
            ),
            // mandatory format as directory name
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates_@format',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => false,
                'pattern' => '@templateRoot/@subpackage/@controller/@action',
                'expectedResult' => array(
                    'Resources/Private/Templates_json/Some/Sub/Package/SomeController/@action',
                )
            ),
            // paths must not contain double slashes
            array(
                'package' => 'Some.Package',
                'subPackage' => null,
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Some/Root/Path/',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Some/Root/Path/SomeController/@action.html',
                    'Some/Root/Path/SomeController/@action',
                    'Some/Root/Path/@action.html',
                    'Some/Root/Path/@action',
                )
            ),
            // paths must be unique
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => 'foo',
                'expectedResult' => array(
                    'foo',
                )
            ),
            // template fallback paths
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => array('Resources/Private/Templates', 'Some/Fallback/Path'),
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action',
                )
            ),
            // template fallback paths with bubbleControllerAndSubpackage
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => array('Resources/Private/Templates', 'Some/Fallback/Path'),
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => array(
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/@action.html',
                    'Some/Fallback/Path/Sub/Package/@action.html',
                    'Some/Fallback/Path/Package/@action.html',
                    'Some/Fallback/Path/@action.html',
                )
            ),
            // partial fallback paths
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => array('Default/Resources/Path', 'Fallback/'),
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@partialRoot/@subpackage/@controller/@partial.@format',
                'expectedResult' => array(
                    'Default/Resources/Path/Some/Sub/Package/SomeController/@partial.html',
                    'Default/Resources/Path/Some/Sub/Package/SomeController/@partial',
                    'Fallback/Some/Sub/Package/SomeController/@partial.html',
                    'Fallback/Some/Sub/Package/SomeController/@partial',
                )
            ),
            // partial fallback paths with bubbleControllerAndSubpackage
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => array('Resources/Private/Templates', 'Some/Fallback/Path'),
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => array('Default/Resources/Path', 'Fallback1/', 'Fallback2'),
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@partialRoot/@controller/@subpackage/@partial',
                'expectedResult' => array(
                    'Default/Resources/Path/SomeController/Some/Sub/Package/@partial',
                    'Default/Resources/Path/Some/Sub/Package/@partial',
                    'Default/Resources/Path/Sub/Package/@partial',
                    'Default/Resources/Path/Package/@partial',
                    'Default/Resources/Path/@partial',
                    'Fallback1/SomeController/Some/Sub/Package/@partial',
                    'Fallback1/Some/Sub/Package/@partial',
                    'Fallback1/Sub/Package/@partial',
                    'Fallback1/Package/@partial',
                    'Fallback1/@partial',
                    'Fallback2/SomeController/Some/Sub/Package/@partial',
                    'Fallback2/Some/Sub/Package/@partial',
                    'Fallback2/Sub/Package/@partial',
                    'Fallback2/Package/@partial',
                    'Fallback2/@partial',
                )
            ),
            // layout fallback paths
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => array('Resources/Private/Templates', 'Some/Fallback/Path'),
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => array('foo', 'bar'),
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => array('Default/Layout/Path', 'Fallback/Path'),
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => false,
                'pattern' => '@layoutRoot/@subpackage/@controller/@layout.@format',
                'expectedResult' => array(
                    'Default/Layout/Path/Some/Sub/Package/SomeController/@layout.html',
                    'Fallback/Path/Some/Sub/Package/SomeController/@layout.html',
                )
            ),
            // layout fallback paths with bubbleControllerAndSubpackage
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => array('Resources/Layouts', 'Some/Fallback/Path'),
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => 'Static/@layoutRoot/@subpackage/@controller/@layout.@format',
                'expectedResult' => array(
                    'Static/Resources/Layouts/Some/Sub/Package/SomeController/@layout.html',
                    'Static/Resources/Layouts/Some/Sub/Package/SomeController/@layout',
                    'Static/Resources/Layouts/Some/Sub/Package/@layout.html',
                    'Static/Resources/Layouts/Some/Sub/Package/@layout',
                    'Static/Resources/Layouts/Sub/Package/@layout.html',
                    'Static/Resources/Layouts/Sub/Package/@layout',
                    'Static/Resources/Layouts/Package/@layout.html',
                    'Static/Resources/Layouts/Package/@layout',
                    'Static/Resources/Layouts/@layout.html',
                    'Static/Resources/Layouts/@layout',
                    'Static/Some/Fallback/Path/Some/Sub/Package/SomeController/@layout.html',
                    'Static/Some/Fallback/Path/Some/Sub/Package/SomeController/@layout',
                    'Static/Some/Fallback/Path/Some/Sub/Package/@layout.html',
                    'Static/Some/Fallback/Path/Some/Sub/Package/@layout',
                    'Static/Some/Fallback/Path/Sub/Package/@layout.html',
                    'Static/Some/Fallback/Path/Sub/Package/@layout',
                    'Static/Some/Fallback/Path/Package/@layout.html',
                    'Static/Some/Fallback/Path/Package/@layout',
                    'Static/Some/Fallback/Path/@layout.html',
                    'Static/Some/Fallback/Path/@layout',
                )
            ),
            // combined fallback paths
            array(
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => array('Resources/Templates', 'Templates/Fallback1', 'Templates/Fallback2'),
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => array('Resources/Partials'),
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => array('Resources/Layouts', 'Layouts/Fallback1'),
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@templateRoot/@partialRoot/@subpackage/@controller/foo',
                'expectedResult' => array(
                    'Resources/Layouts/Resources/Templates/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Resources/Templates/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Resources/Layouts/Templates/Fallback1/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Templates/Fallback1/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Resources/Layouts/Templates/Fallback2/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Templates/Fallback2/Resources/Partials/Some/Sub/Package/SomeController/foo',
                )
            ),
        );
    }

    /**
     * @test
     * @dataProvider expandGenericPathPatternDataProvider()
     *
     * @param string $package
     * @param string $subPackage
     * @param string $controller
     * @param string $format
     * @param string $templateRootPath
     * @param array $templateRootPaths
     * @param string $partialRootPath
     * @param array $partialRootPaths
     * @param string $layoutRootPath
     * @param array $layoutRootPaths
     * @param boolean $bubbleControllerAndSubpackage
     * @param boolean $formatIsOptional
     * @param string $pattern
     * @param string $expectedResult
     */
    public function expandGenericPathPatternTests($package, $subPackage, $controller, $format, $templateRootPath, array $templateRootPaths = null, $partialRootPath, array $partialRootPaths = null, $layoutRootPath, array $layoutRootPaths = null, $bubbleControllerAndSubpackage, $formatIsOptional, $pattern, $expectedResult)
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving($package, $subPackage, $controller, $format);

        /** @var \TYPO3\Fluid\View\TemplateView $templateView */
        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('dummy'), array(), '', false);
        $templateView->setControllerContext($mockControllerContext);
        if ($templateRootPath !== null) {
            $templateView->setTemplateRootPath($templateRootPath);
        }
        if ($templateRootPaths !== null) {
            $templateView->setTemplateRootPaths($templateRootPaths);
        }

        if ($partialRootPath !== null) {
            $templateView->setPartialRootPath($partialRootPath);
        }
        if ($partialRootPaths !== null) {
            $templateView->setPartialRootPaths($partialRootPaths);
        }

        if ($layoutRootPath !== null) {
            $templateView->setLayoutRootPath($layoutRootPath);
        }
        if ($layoutRootPaths !== null) {
            $templateView->setLayoutRootPaths($layoutRootPaths);
        }

        $actualResult = $templateView->_call('expandGenericPathPattern', $pattern, $bubbleControllerAndSubpackage, $formatIsOptional);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithBubblingDisabledAndFormatNotOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', null, 'My', 'html');

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('getTemplateRootPaths', 'getPartialRootPath', 'getLayoutRootPath'));
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(array('Resources/Private/')));

        $expected = array('Resources/Private/Templates/My/@action.html');
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', false, false);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatNotOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('getTemplateRootPaths', 'getPartialRootPath', 'getLayoutRootPath'));
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(array('Resources/Private/')));
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', false, false);

        $expected = array(
            'Resources/Private/Templates/MySubPackage/My/@action.html'
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('getTemplateRootPaths', 'getPartialRootPath', 'getLayoutRootPath'));
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(array('Resources/Private/')));
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', false, true);

        $expected = array(
            'Resources/Private/Templates/MySubPackage/My/@action.html',
            'Resources/Private/Templates/MySubPackage/My/@action'
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingEnabledAndFormatOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('getTemplateRootPaths', 'getPartialRootPath', 'getLayoutRootPath'));
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(array('Resources/Private/')));
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', true, true);

        $expected = array(
            'Resources/Private/Templates/MySubPackage/My/@action.html',
            'Resources/Private/Templates/MySubPackage/My/@action',
            'Resources/Private/Templates/MySubPackage/@action.html',
            'Resources/Private/Templates/MySubPackage/@action',
            'Resources/Private/Templates/@action.html',
            'Resources/Private/Templates/@action'
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getTemplateRootPathsReturnsUserSpecifiedTemplatePaths()
    {
        $templateView = new TemplateView();

        $templateRootPaths = array('/foo/bar', 'baz');
        $templateView->setOption('templateRootPaths', $templateRootPaths);

        $actual = $templateView->getTemplateRootPaths();
        $this->assertEquals($templateRootPaths, $actual, 'A set template root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPartialRootPathsReturnsUserSpecifiedPartialPath()
    {
        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('dummy'));

        $partialRootPaths = array('/foo/bar', 'baz');
        $templateView->setOption('partialRootPaths', $partialRootPaths);

        $actual = $templateView->_call('getPartialRootPaths');
        $this->assertEquals($partialRootPaths, $actual, 'A set partial root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getLayoutRootPathsReturnsUserSpecifiedPartialPaths()
    {
        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('dummy'));

        $layoutRootPaths = array('/foo/bar', 'baz');
        $templateView->setOption('layoutRootPaths', $layoutRootPaths);

        $actual = $templateView->_call('getLayoutRootPaths');
        $this->assertEquals($layoutRootPaths, $actual, 'A set layout root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function pathToPartialIsResolvedCorrectly()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials');
        \file_put_contents('vfs://MyPartials/SomePartial', 'contentsOfSomePartial');

        $paths = array(
            'vfs://NonExistentDir/UnknowFile.html',
            'vfs://MyPartials/SomePartial.html',
            'vfs://MyPartials/SomePartial'
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@partialRoot/@subpackage/@partial.@format', true, true)->will($this->returnValue($paths));

        $templateView->setOption('templateRootPaths', array('MyTemplates'));
        $templateView->setOption('partialRootPaths', array('MyPartials'));
        $templateView->setOption('layoutRootPaths', array('MyLayouts'));

        $this->assertSame('contentsOfSomePartial', $templateView->_call('getPartialSource', 'SomePartial'));
    }

    /**
     * @test
     */
    public function getTemplateSourceChecksDifferentPathPatternsAndReturnsTheFirstPathWhichExists()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates');
        \file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

        $paths = array(
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/@action.html'
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', false, false)->will($this->returnValue($paths));

        $templateView->setOption('templateRootPaths', array('MyTemplates'));
        $templateView->setOption('partialRootPaths', array('MyPartials'));
        $templateView->setOption('layoutRootPaths', array('MyLayouts'));

        $this->assertSame('contentsOfMyCoolAction', $templateView->_call('getTemplateSource', 'myCoolAction'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getTemplatePathAndFilenameThrowsExceptionIfNoPathCanBeResolved()
    {
        vfsStreamWrapper::register();
        $paths = array(
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://NonExistentDir/AnotherUnknownFile.html',
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', false, false)->will($this->returnValue($paths));

        $templateView->_call('getTemplatePathAndFilename', 'myCoolAction');
    }


    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getTemplatePathAndFilenameThrowsExceptionIfResolvedPathPointsToADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates/NotAFile');
        $paths = array(
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/NotAFile'
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', false, false)->will($this->returnValue($paths));

        $templateView->_call('getTemplatePathAndFilename', 'myCoolAction');
    }

    /**
     * @test
     */
    public function resolveTemplatePathAndFilenameReturnsTheExplicitlyConfiguredTemplatePathAndFilename()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates');
        \file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('dummy'));
        $templateView->setOption('templatePathAndFilename', 'vfs://MyTemplates/MyCoolAction.html');

        $this->assertSame('contentsOfMyCoolAction', $templateView->_call('getTemplateSource'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfNoPathCanBeResolved()
    {
        vfsStreamWrapper::register();
        $paths = array(
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://NonExistentDir/AnotherUnknownFile.html',
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@layoutRoot/@layout.@format', true, true)->will($this->returnValue($paths));

        $templateView->_call('getLayoutPathAndFilename', 'SomeLayout');
    }


    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfResolvedPathPointsToADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates/NotAFile');
        $paths = array(
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/NotAFile'
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@layoutRoot/@layout.@format', true, true)->will($this->returnValue($paths));

        $templateView->_call('getLayoutPathAndFilename', 'SomeLayout');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfNoPathCanBeResolved()
    {
        vfsStreamWrapper::register();
        $paths = array(
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://NonExistentDir/AnotherUnknownFile.html',
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@partialRoot/@subpackage/@partial.@format', true, true)->will($this->returnValue($paths));

        $templateView->_call('getPartialPathAndFilename', 'SomePartial');
    }


    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfResolvedPathPointsToADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates/NotAFile');
        $paths = array(
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/NotAFile'
        );

        $templateView = $this->getAccessibleMock(\TYPO3\Fluid\View\TemplateView::class, array('expandGenericPathPattern'));
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@partialRoot/@subpackage/@partial.@format', true, true)->will($this->returnValue($paths));

        $templateView->_call('getPartialPathAndFilename', 'SomePartial');
    }
}
